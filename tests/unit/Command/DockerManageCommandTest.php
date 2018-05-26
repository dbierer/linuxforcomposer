<?php

/**
 * Linux for PHP/Linux for Composer
 *
 * Copyright 2010 - 2018 A. Caya <andrewscaya@yahoo.ca>
 * Version 0.9.9
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 * @package    Linux for PHP/Linux for Composer
 * @copyright  Copyright 2010 - 2018 A. Caya <andrewscaya@yahoo.ca>
 * @link       http://linuxforphp.net/
 * @license    GNU/GPLv2, see above
 * @since 0.9.8
 */

namespace LinuxforcomposerTest\Command;

use Linuxforcomposer\Command\DockerManageCommand;
use LinuxforcomposerTest\Mock\InputMock;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DockerManageCommandTest extends KernelTestCase
{
    protected $dockerCommandMock;

    public static function setUpBeforeClass()
    {
        if (!defined('PHARFILENAME')) {
            define(
                'PHARFILENAME',
                dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'app.php'
            );
        }

        if (!defined('JSONFILEDIST')) {
            define(
                'JSONFILEDIST',
                dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.dist.json'
            );
        }

        if (!defined('JSONFILE')) {
            define(
                'JSONFILE',
                dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
                . DIRECTORY_SEPARATOR
                . 'linuxforcomposer.test.json'
            );
        }

        if (!defined('VENDORFOLDERPID')) {
            define(
                'VENDORFOLDERPID',
                dirname(__DIR__)
                . DIRECTORY_SEPARATOR
                . 'app'
            );
        }
    }

    public function tearDown()
    {
        \Mockery::close();

        parent::tearDown();
    }

    public function createMocksForUnixEnv()
    {
        $this->dockerCommandMock = \Mockery::mock('overload:Symfony\Component\Process\Process');
        $this->dockerCommandMock
            ->shouldReceive('setTimeout')
            ->once()
            ->with(null);
        $this->dockerCommandMock
            ->shouldReceive('setTty')
            ->once()
            ->with(true);
        $this->dockerCommandMock
            ->shouldReceive('start')
            ->once();
        $this->dockerCommandMock
            ->shouldReceive('wait')
            ->once();
    }

    public function testCheckImageWithImageAvailabilitySuccess()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $this->createMocksForUnixEnv();

        $this->dockerCommandMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('We downloaded the image!');
        $this->dockerCommandMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('One download failed');
        $this->dockerCommandMock
            ->shouldReceive('getExitCode')
            ->once()
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandReflection = new \ReflectionClass($command);

        $methodsList = $commandReflection->getMethods();

        for ($i = 0; $i < count($methodsList); $i++) {
            $key = $methodsList[$i]->name;
            $commandMethods[$key] = $methodsList[$i];
            $commandMethods[$key]->setAccessible(true);
        }

        $output = $commandMethods['checkImage']->invokeArgs(
            $command,
            array('7.2.5-nts', 'nts', 'lfphp')
        );

        $this->assertSame(
            ' asclinux/linuxforphp-8.1:7.2.5-nts lfphp',
            $output
        );

        $this->assertSame(
            PHP_EOL
            . 'Checking for image availability and downloading if necessary.'
            . PHP_EOL
            . 'This may take a few minutes...'
            . PHP_EOL
            . 'We downloaded the image!'
            . PHP_EOL
            . 'One download failed'
            . PHP_EOL
            . 'Done!'
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );

        $output2 = $commandMethods['checkImage']->invokeArgs(
            $command,
            array('7.1.16-zts', 'zts', '/bin/bash')
        );

        $this->assertSame(
            ' asclinux/linuxforphp-8.1:7.1.16-zts /bin/bash',
            $output2
        );

        $output3 = $commandMethods['checkImage']->invokeArgs(
            $command,
            array('7.0.29-nts', 'nts', '/bin/bash')
        );

        $this->assertSame(
            ' asclinux/linuxforphp-8.1:7.0.29-nts /bin/bash',
            $output3
        );
    }

    public function testCheckImageWithImageAvailabilityFailure()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $this->createMocksForUnixEnv();

        $this->dockerCommandMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('We downloaded the image!');
        $this->dockerCommandMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('One download failed');
        $this->dockerCommandMock
            ->shouldReceive('getExitCode')
            ->once()
            ->andReturn(1);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandReflection = new \ReflectionClass($command);

        $methodsList = $commandReflection->getMethods();

        for ($i = 0; $i < count($methodsList); $i++) {
            $key = $methodsList[$i]->name;
            $commandMethods[$key] = $methodsList[$i];
            $commandMethods[$key]->setAccessible(true);
        }

        $output = $commandMethods['checkImage']->invokeArgs(
            $command,
            array('7.3.5-nts', 'nts', 'lfphp')
        );

        $this->assertSame(
            ' asclinux/linuxforphp-8.1:src '
            . '/bin/bash -c \'cd ; wget -O tmp http://bit.ly/2jheBrr ; /bin/bash ./tmp 7.3.5 nts ; lfphp\'',
            $output
        );

        $this->assertSame(
            PHP_EOL
            . 'Checking for image availability and downloading if necessary.'
            . PHP_EOL
            . 'This may take a few minutes...'
            . PHP_EOL
            . 'We downloaded the image!'
            . PHP_EOL
            . 'One download failed'
            . PHP_EOL
            . 'Done!'
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testFormatInput()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $this->createMocksForUnixEnv();

        $this->dockerCommandMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('We downloaded the image!');
        $this->dockerCommandMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('One download did fail');
        $this->dockerCommandMock
            ->shouldReceive('getExitCode')
            ->once()
            ->andReturn(0);

        $dockerManageCommandFake = new DockerManageCommand();
        $commandReflection = new \ReflectionClass($dockerManageCommandFake);

        $methodsList = $commandReflection->getMethods();

        for ($i = 0; $i < count($methodsList); $i++) {
            $key = $methodsList[$i]->name;
            $commandMethods[$key] = $methodsList[$i];
            $commandMethods[$key]->setAccessible(true);
        }

        $arguments = array(
            'command' => 'docker:run',
            'interactive' => true,
            'tty'  => true,
            'detached'  => true,
            'phpversion' => '7.2.5',
            'threadsafe' => 'nts',
            'port' => '8181:80',
            'volume' => '${PWD}/:/srv/www',
            'script' => 'lfphp',
            'execute' => 'run',
        );

        $arrayInputFake = new InputMock();
        $arrayInputFake->setArguments($arguments);

        $output = $commandMethods['formatInput']->invokeArgs(
            $dockerManageCommandFake,
            array($arrayInputFake)
        );

        $this->assertSame(
            'docker run --restart=always -i -t -d -p 8181:80 '
            . '-v ${PWD}/:/srv/www asclinux/linuxforphp-8.1:7.2.5-nts lfphp',
            $output
        );

        $this->assertSame(
            PHP_EOL
            . 'Checking for image availability and downloading if necessary.'
            . PHP_EOL
            . 'This may take a few minutes...'
            . PHP_EOL
            . 'We downloaded the image!'
            . PHP_EOL
            . 'One download did fail'
            . PHP_EOL
            . 'Done!'
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );

        $arguments = array(
            'command' => 'docker:run',
            'interactive' => true,
            'tty'  => true,
            'detached'  => true,
            'phpversion' => '7.2.5',
            'threadsafe' => 'nts',
            'port' => array(
                '8181:80',
                '3306:3306',
            ),
            'volume' => array(
                '${PWD}/:/srv/www',
                '${PWD}/:/srv/test',
            ),
            'script' => 'lfphp',
            'execute' => 'run',
        );

        $arrayInputFake = new InputMock();
        $arrayInputFake->setArguments($arguments);

        $output = $commandMethods['formatInput']->invokeArgs(
            $dockerManageCommandFake,
            array($arrayInputFake)
        );

        $this->assertSame(
            'docker run --restart=always -i -t -d -p 8181:80 -p 3306:3306 '
            . '-v ${PWD}/:/srv/www -v ${PWD}/:/srv/test asclinux/linuxforphp-8.1:7.2.5-nts lfphp',
            $output
        );
    }

    public function testExecuteWithRunCommand()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $this->dockerCommandMock = \Mockery::mock('overload:Symfony\Component\Process\Process');
        $this->dockerCommandMock
            ->shouldReceive('setTimeout')
            ->once()
            ->with(null);
        $this->dockerCommandMock
            ->shouldReceive('setTty')
            ->with(true);
        $this->dockerCommandMock
            ->shouldReceive('start')
            ->once();
        $this->dockerCommandMock
            ->shouldReceive('wait')
            ->once();
        $this->dockerCommandMock
            ->shouldReceive('isSuccessful')
            ->andReturn(true);
        $this->dockerCommandMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('');
        $this->dockerCommandMock
            ->shouldReceive('getErrorOutput')
            ->andReturn('');
        $this->dockerCommandMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'execute'  => 'run',
        ));

        $this->assertSame(
            PHP_EOL
            . 'Checking for image availability and downloading if necessary.'
            . PHP_EOL
            . 'This may take a few minutes...'
            . PHP_EOL
            . 'Done!'
            . PHP_EOL
            . PHP_EOL
            . 'Starting container...'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithRunCommandWithStdoutAndStderrFromCommands()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $this->dockerCommandMock = \Mockery::mock('overload:Symfony\Component\Process\Process');
        $this->dockerCommandMock
            ->shouldReceive('setTimeout')
            ->once()
            ->with(null);
        $this->dockerCommandMock
            ->shouldReceive('setTty')
            ->with(true);
        $this->dockerCommandMock
            ->shouldReceive('start')
            ->once();
        $this->dockerCommandMock
            ->shouldReceive('wait')
            ->once();
        $this->dockerCommandMock
            ->shouldReceive('isSuccessful')
            ->andReturn(true);
        $this->dockerCommandMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Fake containers started...');
        $this->dockerCommandMock
            ->shouldReceive('getErrorOutput')
            ->andReturn('We have received a few errors...');
        $this->dockerCommandMock
            ->shouldReceive('getExitCode')
            ->andReturn(0);

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'execute'  => 'run',
        ));

        $this->assertSame(
            PHP_EOL
            . 'Checking for image availability and downloading if necessary.'
            . PHP_EOL
            . 'This may take a few minutes...'
            . PHP_EOL
            . 'Fake containers started...'
            . PHP_EOL
            . 'We have received a few errors...'
            . PHP_EOL
            . 'Done!'
            . PHP_EOL
            . PHP_EOL
            . 'Starting container...'
            . PHP_EOL
            . 'Fake containers started...'
            . PHP_EOL
            . 'We have received a few errors...'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStopCommand()
    {
        file_put_contents(
            VENDORFOLDERPID
            . DIRECTORY_SEPARATOR
            . 'composer'
            . DIRECTORY_SEPARATOR
            . 'linuxforcomposer.pid',
            'a1a1' . PHP_EOL
        );

        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $this->dockerCommandMock = \Mockery::mock('overload:Symfony\Component\Process\Process');
        $this->dockerCommandMock
            ->shouldReceive('setTimeout')
            ->once()
            ->with(null);
        $this->dockerCommandMock
            ->shouldReceive('setTty')
            ->once()
            ->with(true);
        $this->dockerCommandMock
            ->shouldReceive('start')
            ->once();
        $this->dockerCommandMock
            ->shouldReceive('wait')
            ->once();
        $this->dockerCommandMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('');
        $this->dockerCommandMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'execute'  => 'stop',
        ));

        $this->assertSame(
            'Stopping container...' . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStopCommandWithStdoutAndStderrFromCommands()
    {
        file_put_contents(
            VENDORFOLDERPID
            . DIRECTORY_SEPARATOR
            . 'composer'
            . DIRECTORY_SEPARATOR
            . 'linuxforcomposer.pid',
            'a1a1' . PHP_EOL
        );

        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $this->dockerCommandMock = \Mockery::mock('overload:Symfony\Component\Process\Process');
        $this->dockerCommandMock
            ->shouldReceive('setTimeout')
            ->once()
            ->with(null);
        $this->dockerCommandMock
            ->shouldReceive('setTty')
            ->once()
            ->with(true);
        $this->dockerCommandMock
            ->shouldReceive('start')
            ->once();
        $this->dockerCommandMock
            ->shouldReceive('wait')
            ->once();
        $this->dockerCommandMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('Fake containers stopped and removed!');
        $this->dockerCommandMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('We have received a few errors...');

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'execute'  => 'stop',
        ));

        $this->assertSame(
            'Stopping container...'
            . PHP_EOL
            . 'Fake containers stopped and removed!'
            . PHP_EOL
            . 'We have received a few errors...'
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStopCommandWithEmptyPidFile()
    {
        file_put_contents(
            VENDORFOLDERPID
            . DIRECTORY_SEPARATOR
            . 'composer'
            . DIRECTORY_SEPARATOR
            . 'linuxforcomposer.pid',
            '' . PHP_EOL
        );

        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $this->dockerCommandMock = \Mockery::mock('overload:Symfony\Component\Process\Process');
        $this->dockerCommandMock
            ->shouldReceive('setTimeout')
            ->once()
            ->with(1800);
        $this->dockerCommandMock
            ->shouldReceive('setTty')
            ->once()
            ->with(true);
        $this->dockerCommandMock
            ->shouldReceive('start')
            ->once();
        $this->dockerCommandMock
            ->shouldReceive('wait')
            ->once();
        $this->dockerCommandMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('');
        $this->dockerCommandMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'execute'  => 'stop',
        ));

        $this->assertSame(
            PHP_EOL
            . 'PID file was empty!'
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithStopCommandWithoutPidFile()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $this->dockerCommandMock = \Mockery::mock('overload:Symfony\Component\Process\Process');
        $this->dockerCommandMock
            ->shouldReceive('setTimeout')
            ->once()
            ->with(1800);
        $this->dockerCommandMock
            ->shouldReceive('setTty')
            ->once()
            ->with(true);
        $this->dockerCommandMock
            ->shouldReceive('start')
            ->once();
        $this->dockerCommandMock
            ->shouldReceive('wait')
            ->once();
        $this->dockerCommandMock
            ->shouldReceive('getOutput')
            ->once()
            ->andReturn('');
        $this->dockerCommandMock
            ->shouldReceive('getErrorOutput')
            ->once()
            ->andReturn('');

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'execute'  => 'stop',
        ));

        $this->assertSame(
            PHP_EOL
            . 'Could not find the PID file!'
            . PHP_EOL
            . 'Please make sure the file exists or stop the containers manually.'
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );
    }

    public function testExecuteWithWrongCommand()
    {
        // Redirect output to command output
        $this->setOutputCallback(function () {
        });

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->add(new DockerManageCommand());

        $command = $application->find('docker:manage');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'execute'  => 'bad',
        ));

        $this->assertSame(
            PHP_EOL
            . 'Wrong command given!'
            . PHP_EOL
            . PHP_EOL,
            $this->getActualOutput()
        );
    }
}
