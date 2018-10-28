<?php

/**
 * Linux for PHP/Linux for Composer
 *
 * Copyright 2010 - 2018 Foreach Code Factory <lfphp@asclinux.net>
 * Version 1.0.0-dev
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package    Linux for PHP/Linux for Composer
 * @copyright  Copyright 2010 - 2018 Foreach Code Factory <lfphp@asclinux.net>
 * @link       http://linuxforphp.net/
 * @license    Apache License, Version 2.0, see above
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 * @since 1.0.0
 */

namespace Linuxforcomposer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class DockerCommitCommand extends Command
{
    const LFPHPDEFAULTVERSION = DockerManageCommand::LFPHPDEFAULTVERSION;

    protected static $defaultName = 'docker:commit';

    public function __construct()
    {
        // you *must* call the parent constructor
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('docker:commit')
            ->setDescription('Docker commit commands.');
        $this
            // configure an argument
            ->addArgument('pid', InputArgument::REQUIRED, 'The Docker PID to commit.')
            // configure an argument
            ->addArgument('name', InputArgument::REQUIRED, 'Committed version\'s name.')
            // configure options
            ->addOption(
                'savetojsonfile',
                's',
                InputOption::VALUE_REQUIRED,
                'Save the version\'s name to the linuxforcomposer.json file at the specified position.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jsonFile = JSONFILE;

        $fileContentsJson = file_get_contents($jsonFile);

        $fileContentsArray = json_decode($fileContentsJson, true);

        if ($fileContentsArray['thread-safe'] === 'true') {
            $threadsafe = '-zts';
        } else {
            $threadsafe = '-nts';
        }

        $pid = (string) $input->getArgument('pid');

        $versionName = 'custom-' . $input->getArgument('name');

        $versionNameTS = 'custom-' . $input->getArgument('name') . $threadsafe;

        $name = (string) DockerCommitCommand::LFPHPDEFAULTVERSION . ':' . $versionNameTS;

        $dockerCommitCommand = 'docker commit ' . $pid . ' ' . $name;

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if (strstr(php_uname('v'), 'Windows 10') !== false && php_uname('r') == '10.0') {
                $dockerCommitCommand = 'PowerShell -Command "'
                    . $dockerCommitCommand
                    . '"';
            } else {
                $dockerCommitCommand = 'bash -c "'
                    . $dockerCommitCommand
                    . '"';
            }
        }

        $process = new Process($dockerCommitCommand);

        $process->setTimeout(null);

        if (strtoupper((substr(PHP_OS, 0, 3))) !== 'WIN') {
            $process->setTty(true);
        }

        $process->start();

        $process->wait();

        $processStdout = $process->getOutput();

        $processStderr = $process->getErrorOutput();

        if (!empty($processStdout)) {
            echo $processStdout . PHP_EOL;
        }

        if (!empty($processStderr)) {
            echo $processStderr . PHP_EOL;
        }

        $position = $input->getOption('savetojsonfile');

        if ($position !== null) {
            if ($fileContentsArray === null) {
                echo "WARNING: The linuxforcomposer.json file is empty! The file is unchanged." . PHP_EOL;
                return;
            }

            if (!isset($fileContentsArray['php-versions']) || empty($fileContentsArray['php-versions'])) {
                echo "WARNING: The linuxforcomposer.json file is invalid! The file is unchanged." . PHP_EOL;
                return;
            }

            $fileContentsArray['php-versions'][$position] = $versionName;

            $fileContentsJson = json_encode($fileContentsArray, JSON_PRETTY_PRINT);

            file_put_contents($jsonFile, $fileContentsJson);
        }
    }
}
