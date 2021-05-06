<?php

declare(strict_types=1);


/**
 * Some tools for myself.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021, Maxence Lange <maxence@artificial-owl.com>
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace daita\MySmallPhpTools\Console\Nextcloud\nc22;


use daita\MySmallPhpTools\Exceptions\ShellConfirmationException;
use daita\MySmallPhpTools\Exceptions\ShellMissingCommandException;
use daita\MySmallPhpTools\Exceptions\ShellMissingItemException;
use daita\MySmallPhpTools\Exceptions\ShellUnknownCommandException;
use daita\MySmallPhpTools\Exceptions\ShellUnknownItemException;
use daita\MySmallPhpTools\IInteractiveShellClient;
use daita\MySmallPhpTools\Model\Nextcloud\nc22\NC22InteractiveShellSession;
use daita\MySmallPhpTools\Traits\TStringTools;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;


/**
 * Class InteractiveShell
 *
 * @package daita\MySmallPhpTools\Console\Nextcloud\nc22
 */
class NC22InteractiveShell {


	use TStringTools;


	/** @var InputInterface */
	private $input;

	/** @var OutputInterface */
	private $output;

	/** @var IInteractiveShellClient */
	private $client;


	/** @var QuestionHelper */
	private $helper;

	/** @var array */
	private $commands = [];


	public function __construct(
		Command $parent,
		InputInterface $input,
		IInteractiveShellClient $client
	) {
		$this->helper = $parent->getHelper('question');
		$this->input = $input;
		$this->client = $client;

		$output = new ConsoleOutput();
		$this->output = $output->section();
	}


	/**
	 * @param array $commands
	 */
	public function setCommands(array $commands): void {
		sort($commands);
		$this->commands = array_merge($commands, ['quit', 'help']);
	}

	/**
	 * @param array $command
	 */
	public function addCommand(string $command): void {
		$this->commands[] = $command;
	}


	/**
	 * @param string $prompt
	 */
	public function run(string $prompt = '%PATH%>'): void {
//		$path = [];
		$session = new NC22InteractiveShellSession();
		while (true) {
			$question = new Question(trim(str_replace('%PATH%', $path, $prompt)) . ' ', '');
			$this->manageAvailableCommands($session);
			$question->setAutocompleterValues($session->getAvailableCommands());

			$current = strtolower($this->helper->ask($this->input, $this->output, $question));
			if ($current === 'quit' || $current === 'q' || $current === 'exit') {
				exit();
			}

			if ($current === '?' || $current === 'help') {
				$this->listCurrentAvailableCommands($session->getAvailableCommands());
				continue;
			}

			if ($current === '') {
				$path = '';
				continue;
			}

			$command = ($path === '') ? $current : str_replace('.', ' ', $path) . ' ' . $current;

			try {
				$this->client->manageCommand($command);
				$path = str_replace(' ', '.', $command);
			} catch (ShellMissingCommandException $e) {
				foreach ($this->commands as $cmd) {
					$tmp = trim($this->commonPart(str_replace(' ', '.', $command), $cmd), '.');
					if (strlen($tmp) > strlen($path)) {
						$path = $tmp;
					}
				}
			} catch (ShellMissingItemException $e) {
				$more = ($e->getMessage()) ? ' - ' . $e->getMessage() : '';
				$this->output->writeln(
					'<comment>' . $command . '</comment>: missing item(s)' . $more
				);
			} catch (ShellUnknownItemException $e) {
				$more = ($e->getMessage()) ? ' - ' . $e->getMessage() : '';
				$this->output->writeln('<comment>' . $current . '</comment>: unknown item' . $more);
			} catch (ShellUnknownCommandException $e) {
				$more = ($e->getMessage()) ? ' - ' . $e->getMessage() : '';
				$this->output->writeln(
					'<comment>' . $command . '</comment>: command not found' . $more
				);
			}
		}
	}


	/**
	 * @param NC22InteractiveShellSession $session
	 */
	private function manageAvailableCommands(NC22InteractiveShellSession $session): void {
		$commands = [];
		foreach ($this->commands as $command) {
			if (strpos($command, $session->getPath()) !== 0) {
				break;
			}

			$commands[] = $command;
		}

		$session->setAvailableCommands($commands);
	}

//
//	/**
//	 * @param string $path
//	 *
//	 * @return string[]
//	 */
//	private function generateInteractiveShellItem(string $path): InteractiveShellItem {
//		$commands = [];
//		foreach ($this->commands as $command) {
//			$pathItems = explode('.', $path);
//			$commandItems = explode('.', $command);
//
//			foreach ($pathCommand as $item) {
//
//			}
//
//
////			if ($path !== '' && strpos($entry, $path) === false) {
////				continue;
////			}
////
////			$subPath = explode('.', $path);
////			$list = explode('.', $entry);
////
////			$root = [''];
////			for ($i = 0; $i < sizeof($list); $i++) {
////				$sub = $list[$i];
////				if ($sub === $subPath[$i]) {
////					continue;
////				}
////				$this->parseSubCommand($commands, $root, $sub);
////			}
//		}
//
//		return $commands;
//	}


	/**
	 * @param array $commands
	 */
	private function listCurrentAvailableCommands(array $commands): void {
		foreach ($commands as $command) {
			if (strpos($command, ' ') === false) {
				$this->output->writeln('<info>' . $command . '</info>');
			}
		}
	}


	/**
	 * @param array $commands
	 * @param array $root
	 * @param string $sub
	 */
	private function parseSubCommand(array &$commands, array &$root, string $sub): void {

		if (substr($sub, 0, 1) === '?') {
			$cmd = substr($sub, 1);
//			$list = $this->client->fillCommandList($root, $cmd);
		} else {
			$list = [$sub];
		}

		$newRoot = [];
		foreach ($list as $sub) {
			foreach ($root as $r) {
				$command = ($r === '') ? $sub : $r . ' ' . $sub;
				if (!in_array($command, $commands)) {
					$commands[] = $command;
				}

				$newRoot[] = $command;
			}

		}

		$root = $newRoot;
	}


	/**
	 * @param string $asking
	 * @param string $default
	 * @param array $range
	 *
	 * @return string
	 */
	public function asking(string $asking, string $default = '', array $range = []): string {
		while (true) {
			$question = new Question('> <info>' . $asking . '</info>: ', $default);
			$question->setAutocompleterValues($range);
			$answer = $this->helper->ask($this->input, $this->output, $question);
			if (empty($range) || in_array($answer, $range)) {
				return $answer;
			}

			$this->output->writeln('<comment>Unknown value</comment>');
		}
	}


	/**
	 * @param string $action
	 *
	 * @param bool $default
	 *
	 * @throws ShellConfirmationException
	 */
	public function confirming(string $action = 'Continue with this action?', bool $default = false): void {
		$confirm = new ConfirmationQuestion(trim($action) . ' ', $default);
		if (!$this->helper->ask($this->input, $this->output, $confirm)) {
			throw new ShellConfirmationException();
		}
	}

}

