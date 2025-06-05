<?php
namespace Portfolion\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ChoiceQuestion;

abstract class BaseCommand extends Command {
    protected function configure() {
        if (isset($this->arguments)) {
            foreach ($this->arguments as $name => $description) {
                $this->addArgument($name, InputArgument::REQUIRED, $description);
            }
        }
        
        if (isset($this->options)) {
            foreach ($this->options as $name => $description) {
                // Check if the option name indicates it's a flag (boolean)
                $isFlag = (strpos($name, 'skip-') === 0 || 
                          strpos($name, 'no-') === 0 || 
                          strpos($name, 'with-') === 0 ||
                          in_array($name, ['help', 'quiet', 'verbose', 'version', 'ansi', 'no-ansi', 'no-interaction', 'coverage', 'stop-on-failure']));
                
                $mode = $isFlag ? InputOption::VALUE_NONE : InputOption::VALUE_OPTIONAL;
                
                // Get short option (first character) if it's a single word
                $shortcut = (!strpos($name, '-') && strlen($name) > 1) ? substr($name, 0, 1) : null;
                
                $this->addOption($name, $shortcut, $mode, $description);
            }
        }
    }
    
    abstract protected function handle(InputInterface $input, OutputInterface $output): int;
    
    protected function execute(InputInterface $input, OutputInterface $output): int {
        try {
            return $this->handle($input, $output);
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
    
    protected function info(OutputInterface $output, string $message): void {
        $output->writeln("<info>{$message}</info>");
    }
    
    protected function error(OutputInterface $output, string $message): void {
        $output->writeln("<error>{$message}</error>");
    }
    
    protected function warning(OutputInterface $output, string $message): void {
        $output->writeln("<comment>{$message}</comment>");
    }
    
    protected function success(OutputInterface $output, string $message): void {
        $output->writeln("<fg=green>{$message}</>");
    }
    
    protected function table(OutputInterface $output, array $headers, array $rows): void {
        $table = new Table($output);
        $table
            ->setHeaders($headers)
            ->setRows($rows)
            ->render();
    }
    
    protected function confirm(InputInterface $input, OutputInterface $output, string $question): bool {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion($question . ' (yes/no) [no]: ', false);
        
        return $helper->ask($input, $output, $question);
    }
    
    protected function secret(InputInterface $input, OutputInterface $output, string $question): ?string {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new Question($question);
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        
        return $helper->ask($input, $output, $question);
    }
    
    protected function choice(InputInterface $input, OutputInterface $output, string $question, array $choices): string {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion($question, $choices);
        
        return $helper->ask($input, $output, $question);
    }
}
