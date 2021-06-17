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


namespace ArtificialOwl\MySmallPhpTools\Console\Nextcloud\nc22;


use ArtificialOwl\MySmallPhpTools\Exceptions\ShellConfirmationException;
use ArtificialOwl\MySmallPhpTools\IInteractiveShellClient;
use ArtificialOwl\MySmallPhpTools\Model\Nextcloud\nc22\NC22InteractiveShellSession;
use ArtificialOwl\MySmallPhpTools\Traits\TStringTools;
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
 * @package ArtificialOwl\MySmallPhpTools\Console\Nextcloud\nc22
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
		$this->output = new ConsoleOutput();
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
	public function run(?NC22InteractiveShellSession $session = null): void {
		if (is_null($session)) {
			$session = new NC22InteractiveShellSession();
		}

		$session->setCommands($this->commands);
		while (true) {
			$this->client->onNewPrompt($session);
			$question = new Question(
				trim(
					str_replace(
						'%PATH%',
						$session->getPath(),
						$session->getPrompt()
					)
				) . ' ',
				''
			);

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
				$session->goParent();
				continue;
			}

			$this->manageCommand($session, $current);
//			$path = str_replace(' ', '')
//			$command = ($path === '') ? $current : str_replace('.', ' ', $path) . ' ' . $current;
//
//			try {
//				$this->client->manageCommand($command);
//				$path = str_replace(' ', '.', $command);
//			} catch (ShellMissingCommandException $e) {
//				foreach ($this->commands as $cmd) {
//					$tmp = trim($this->commonPart(str_replace(' ', '.', $command), $cmd), '.');
//					if (strlen($tmp) > strlen($path)) {
//						$path = $tmp;
//					}
//				}
//			} catch (ShellMissingItemException $e) {
//				$more = ($e->getMessage()) ? ' - ' . $e->getMessage() : '';
//				$this->output->writeln(
//					'<comment>' . $command . '</comment>: missing item(s)' . $more
//				);
//			} catch (ShellUnknownItemException $e) {
//				$more = ($e->getMessage()) ? ' - ' . $e->getMessage() : '';
//				$this->output->writeln('<comment>' . $current . '</comment>: unknown item' . $more);
//			} catch (ShellUnknownCommandException $e) {
//				$more = ($e->getMessage()) ? ' - ' . $e->getMessage() : '';
//				$this->output->writeln(
//					'<comment>' . $command . '</comment>: command not found' . $more
//				);
//			}
		}
	}

//
//	private function availableCommands(string $path = ''): array {
//		$commands = [];
//		foreach ($this->commands as $entry) {
//			if ($path !== '' && strpos($entry, $path) === false) {
//				continue;
//			}
//
//			$subPath = explode('.', $path);
//			$list = explode('.', $entry);
//
//			$root = [''];
//			for ($i = 0; $i < sizeof($list); $i++) {
//				$sub = $list[$i];
//				if ($sub === $subPath[$i]) {
//					continue;
//				}
//				$this->parseSubCommand($commands, $root, $sub);
//			}
//		}
//
//		return $commands;
//	}


	/**
	 * @param NC22InteractiveShellSession $session
	 */
	private function manageAvailableCommands(NC22InteractiveShellSession $session): void {
		$availableCommands = [];
		foreach ($this->commands as $command) {
			if ($session->getPath() !== '' && strpos($command, $session->getPath()) !== 0) {
				continue;
			}

			$commands = [];
			$current = '';
			foreach (explode('.', substr($command, strlen($session->getPath()))) as $item) {
				$current .= $item . ' ';
				$commands[] = trim($current, ' ');
			}

			$availableCommands = array_filter(array_merge($availableCommands, $commands));
		}

		$session->setAvailableCommands($availableCommands);
	}


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
	 * @param NC22InteractiveShellSession $session
	 * @param string $command
	 */
	private function manageCommand(NC22InteractiveShellSession $session, string $command): void {
		$command = str_replace(' ', '.', $command);
		if (!$session->isCommandAvailable($this->commands, $command)) {
			return;
		}

		$fullPath = $session->getPath($command);
		$session->addPath($command);

		$this->client->onNewCommand($session, $command);
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

