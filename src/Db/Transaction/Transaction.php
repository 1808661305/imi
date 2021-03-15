<?php

declare(strict_types=1);

namespace Imi\Db\Transaction;

use Imi\Event\TEvent;

class Transaction
{
    use TEvent;

    /**
     * 事务层级计数.
     *
     * @var int
     */
    private int $transactionLevels = 0;

    /**
     * 启动一个事务
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        ++$this->transactionLevels;

        return true;
    }

    /**
     * 提交一个事务
     *
     * @return bool
     */
    public function commit(): bool
    {
        $offEvents = [];
        $levels = $this->transactionLevels;
        $this->transactionLevels = 0;
        $i = $levels;
        try
        {
            for (; $i >= 0; --$i)
            {
                $this->trigger('transaction.' . $i . '.commit', [
                    'db'    => $this,
                    'level' => $i,
                ]);
                $offEvents[] = 'transaction.' . $i . '.rollback';
            }
        }
        catch (\Throwable $th)
        {
            for (; $i >= 0; --$i)
            {
                $offEvents[] = 'transaction.' . $i . '.commit';
                $offEvents[] = 'transaction.' . $i . '.rollback';
            }
            throw $th;
        }
        finally
        {
            $this->off($offEvents);
        }

        return true;
    }

    /**
     * 回滚事务
     * 支持设置回滚事务层数，如果不设置则为全部回滚.
     *
     * @param int|null $levels
     *
     * @return bool
     */
    public function rollBack(?int $levels = null): bool
    {
        $offEvents = [];
        $transactionLevels = &$this->transactionLevels;
        if (null === $levels)
        {
            $final = 0;
        }
        else
        {
            $final = $transactionLevels - $levels;
        }
        $i = $transactionLevels;
        try
        {
            for (; $i >= $final; --$i)
            {
                $this->trigger('transaction.' . $i . '.rollback', [
                    'db'    => $this,
                    'level' => $i,
                ]);
                $offEvents[] = 'transaction.' . $i . '.commit';
            }
        }
        catch (\Throwable $th)
        {
            for (; $i >= $final; --$i)
            {
                $offEvents[] = 'transaction.' . $i . '.commit';
                $offEvents[] = 'transaction.' . $i . '.rollback';
            }
            throw $th;
        }
        finally
        {
            $transactionLevels = $final;
            $this->off($offEvents);
        }

        return true;
    }

    /**
     * 获取事务层数.
     *
     * @return int
     */
    public function getTransactionLevels(): int
    {
        return $this->transactionLevels;
    }

    /**
     * 监听事务提交事件.
     *
     * @param callable $callable
     *
     * @return void
     */
    public function onTransactionCommit(callable $callable): void
    {
        $this->one('transaction.' . $this->transactionLevels . '.commit', $callable);
    }

    /**
     * 监听事务回滚事件.
     *
     * @param callable $callable
     *
     * @return void
     */
    public function onTransactionRollback(callable $callable): void
    {
        $this->one('transaction.' . $this->transactionLevels . '.rollback', $callable);
    }
}
