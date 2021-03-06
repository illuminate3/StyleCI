<?php

/*
 * This file is part of StyleCI.
 *
 * (c) Cachet HQ <support@cachethq.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace StyleCI\StyleCI\Handlers\Commands;

use Exception;
use Psr\Log\LoggerInterface;
use StyleCI\Config\Exceptions\ConfigExceptionInterface;
use StyleCI\Fixer\Report;
use StyleCI\Fixer\ReportBuilder;
use StyleCI\StyleCI\Commands\AnalyseCommitCommand;
use StyleCI\StyleCI\Events\AnalysisHasCompletedEvent;
use StyleCI\StyleCI\Models\Commit;

/**
 * This is the analyse commit command handler.
 *
 * @author Graham Campbell <graham@cachethq.io>
 */
class AnalyseCommitCommandHandler
{
    /**
     * The report builder instance.
     *
     * @var \StyleCI\Fixer\ReportBuilder
     */
    protected $builder;

    /**
     * The logger instance.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Create a new analyse commit command handler instance.
     *
     * @param \StyleCI\Fixer\ReportBuilder $builder
     * @param \Psr\Log\LoggerInterface     $logger
     *
     * @return void
     */
    public function __construct(ReportBuilder $builder, LoggerInterface $logger)
    {
        $this->builder = $builder;
        $this->logger = $logger;
    }

    /**
     * Handle the analyse commit command.
     *
     * @param \StyleCI\StyleCI\Commands\AnalyseCommitCommand $command
     *
     * @return void
     */
    public function handle(AnalyseCommitCommand $command)
    {
        $commit = $command->getCommit();

        try {
            $this->saveReport($this->builder->analyse($commit->name(), $commit->id), $commit);
        } catch (ConfigExceptionInterface $e) {
            $commit->status = 4;
            $commit->error_message = $e->getMessage();
            $commit->save();
        } catch (Exception $e) {
            $commit->status = 3;
            $commit->save();
            $this->logger->error('Analysis errored.', [$e, $commit->toArray()]);
        }

        event(new AnalysisHasCompletedEvent($commit));
    }

    /**
     * Save the main report to the database.
     *
     * @param \StyleCI\Fixer\Report          $report
     * @param \StyleCI\StyleCI\Models\Commit $commit
     *
     * @return void
     */
    protected function saveReport(Report $report, Commit $commit)
    {
        $commit->time = $report->time();
        $commit->memory = $report->memory();
        $commit->diff = $report->diff();

        if ($report->successful()) {
            $commit->status = 1;
        } else {
            $commit->status = 2;
        }

        $commit->save();
    }
}
