<?php
/**
 * Linux for PHP/Linux for Composer
 *
 * Copyright 2010 - 2019 Foreach Code Factory <lfphp@asclinux.net>
 * Version 1.0.2
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
 * @copyright  Copyright 2010 - 2019 Foreach Code Factory <lfphp@asclinux.net>
 * @link       http://linuxforphp.net/
 * @license    Apache License, Version 2.0, see above
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 * @since 0.9.8
 */

namespace Linuxforcomposer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Linuxforcomposer\Helper\LinuxForComposerProcess;

class DockerRunCommand extends Command
{
    const LFPHPCLOUDSERVER = 'https://linuxforphp.com/api/v1/deployments';

    protected static $defaultName = 'docker:run';

    public function __construct()
    {
        // you *must* call the parent constructor
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('docker:run')
            ->setDescription("Run 'Linux for PHP' containers.");
        $this
            // configure arguments
            ->addArgument('execute', InputArgument::REQUIRED, '[start] or [stop] the containers.')
            // configure options
            ->addOption('jsonfile', null, InputOption::VALUE_REQUIRED, 'Use a custom JSON configuration file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        switch ($input->getArgument('execute')) {
            case 'start':
                $dockerManageCommandsArray = $this->getParsedJsonFile($input);

                foreach ($dockerManageCommandsArray as $key => $value) {
                    if (empty($value)) {
                        unset($dockerManageCommandsArray[$key]);
                    }
                }

                foreach ($dockerManageCommandsArray as $key => $dockerManageCommand) {
                    $process = new LinuxForComposerProcess($dockerManageCommand);

                    $process->setTty($process->isTtySupported());

                    $process->setTimeout(null);

                    $process->prepareProcess();

                    $process->start();

                    //$process->run();

                    /*while ($process->isRunning()) {
                        // waiting for process to finish
                        ;
                    }*/

                    $process->wait();

                    $processStdout = $process->getOutput();

                    $processStderr = $process->getErrorOutput();

                    //$output->writeln($process->getOutput());
                    if (!empty($processStdout)) {
                        echo $processStdout . PHP_EOL;
                    }

                    //$output->writeln($process->getErrorOutput());
                    if (!empty($processStderr)) {
                        echo $processStderr . PHP_EOL;
                    }
                }

                break;

            case 'stop-force':
                $stopForce = true;

                // break; Fall through. Deliberately not breaking here.

            case 'stop':
                $dockerManageCommandsArray = $this->getParsedJsonFile($input);

                $stopForce = isset($stopForce) ?: false;

                $stopCommand = $stopForce ? 'stop-force' : 'stop';

                if (($position = strrpos($dockerManageCommandsArray[0], 'build')) !== false) {
                    $searchLength = strlen('build');
                    $dockerManageCommand = substr_replace(
                        $dockerManageCommandsArray[0],
                        $stopCommand,
                        $position, $searchLength
                    );
                }

                if (($position = strrpos($dockerManageCommandsArray[0], 'run')) !== false) {
                    $searchLength = strlen('run');
                    $dockerManageCommand = substr_replace(
                        $dockerManageCommandsArray[0],
                        $stopCommand,
                        $position, $searchLength
                    );
                }

                $process = new LinuxForComposerProcess($dockerManageCommand);

                $process->setTty($process->isTtySupported());

                $process->setTimeout(null);

                $process->prepareProcess();

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

                break;

            case 'deploy':
                set_time_limit(0);

                $this->getParsedJsonFile($input);

                $jsonFile = ($input->getOption('jsonfile')) ?: null;

                if (($jsonFile === null || !file_exists($jsonFile)) && file_exists(JSONFILE)) {
                    $jsonFile = JSONFILE;
                } elseif (($jsonFile === null || !file_exists($jsonFile)) && !file_exists(JSONFILE)) {
                    $jsonFile = JSONFILEDIST;
                }

                $fileContentsJson = file_get_contents($jsonFile);

                $fileContentsArray = json_decode($fileContentsJson, true);

                if ($fileContentsArray === null) {
                    echo PHP_EOL . "The 'Linux for Composer' JSON file is invalid." . PHP_EOL . PHP_EOL;
                    exit;
                }

                $account = $fileContentsArray['lfphp-cloud']['account'];

                $username = $fileContentsArray['lfphp-cloud']['username'];

                $token = $fileContentsArray['lfphp-cloud']['token'];

                $cloudServerUrl = DockerRunCommand::LFPHPCLOUDSERVER . '/' . $account;

                $ch = \curl_init($cloudServerUrl);
                \curl_setopt($ch, CURLOPT_TIMEOUT, 50);
                \curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                \curl_setopt($ch, CURLOPT_POST, true);

                $postData = [
                    'username' => $username,
                    'token' => $token,
                    'json' => $fileContentsJson,
                ];

                if (
                    isset($fileContentsArray['single']['image']['dockerfile'])
                    && !empty($fileContentsArray['single']['image']['dockerfile']['url'])
                ) {
                    $url = $fileContentsArray['single']['image']['dockerfile']['url'];

                    $urlArray = parse_url($url);

                    $pathArray = explode('/', $urlArray['path']);

                    $filename = array_pop($pathArray);

                    $path = BASEDIR . DIRECTORY_SEPARATOR . $filename;

                    if (!isset($urlArray['host']) && !isset($urlArray['scheme'])) {
                        if (file_exists($path)) {
                            $curlFile = curl_file_create($path);
                            $postData['file'] = $curlFile;
                        }
                    }
                }

                \curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                \curl_exec($ch);
                \curl_close($ch);

                if (isset($fp) && is_resource($fp)) {
                    fclose($fp);
                }

                break;

            default:
                echo PHP_EOL . 'Wrong command given!' . PHP_EOL . PHP_EOL;

                break;
        }
    }

    protected function getParsedJsonFile(InputInterface $input)
    {
        $parseCommand = $this->getApplication()->find('docker:parsejson');

        $jsonFile = ($input->getOption('jsonfile')) ?: null;

        if ($jsonFile !== null) {
            $arguments = [
                '--jsonfile' => $jsonFile,
            ];
        } else {
            $arguments = [];
        }

        $parseInput = new ArrayInput($arguments);

        $parseOutput = new BufferedOutput();

        $returnCode = (int) $parseCommand->run($parseInput, $parseOutput);

        if ($returnCode > 1) {
            echo PHP_EOL . 'You must choose at least one PHP version to run.' . PHP_EOL . PHP_EOL;
            exit;
        } elseif ($returnCode === 1) {
            echo PHP_EOL . "The 'Linux for Composer' JSON file is invalid." . PHP_EOL . PHP_EOL;
            exit;
        }

        return explode("\n", $parseOutput->fetch());
    }
}
