<?php

namespace MDM\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Process\Process;

class PreCommitCommand extends Command
{
    const WITH_PICS = false;

    const PHP_CS_FIXER_ENABLE = true;
    const PHP_CS_FIXER_FILTERS = 'linefeed,short_tag,indentation,trailing_spaces,phpdoc_params,extra_empty_lines,controls_spaces,braces,elseif';
    const PHP_CS_FIXER_AUTOADD_GIT = true;

    const PHP_CPD_ENABLE = true;
    const PHP_CPD_MIN_LINES = 5;
    const PHP_CPD_MIN_TOKENS = 50;

    const PHP_MD_ENABLE = true;
    const PHP_MD_RULESET = 'codesize,unusedcode';

    protected function configure()
    {
        $this->setName('check')->setDescription('Scan and check all files added to commit');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->formatter = $this->getHelperSet()->get('formatter');

        // Grab all added, copied or modified files into $output array
        $gitDiffProcess = new Process('git diff --cached --name-status --diff-filter=ACM');
        $gitDiffProcess->run();
        // Transforme the output of an array of list of files
        $files = explode("\n", $gitDiffProcess->getOutput());

        # Git conflict markers
        $stopWordsGit = array(">>>>>>", "<<<<<<", "======");

        $stopWordsPhp = array("var_dump\(", "die\(");

        // JavaScript debug code that would break IE.
        $stopWordsJs = array("console.debug", "console.log", "alert\(");

        $cpt = 0;
        $phpFiles = array();
        $perfectSyntax = true;
        foreach ($files as $file) {
            if("" != $file){
                ++$cpt;
                $fileName = trim(substr($file, 1));
                $fileInfo = pathinfo($fileName, PATHINFO_EXTENSION);
                switch ($fileInfo) {
                    case "php":
                        $phpFiles[] = $fileName;

                        // Check syntax with PHP lint
                        $lint_output = array();
                        $phpLintProcess = new Process('git diff --cached --name-status --diff-filter=ACM');
                        $phpLintProcess->run();
                        exec("php -l " . escapeshellarg($fileName) . " 2>&1", $lint_output, $return);
                        $this->logError($output, $lint_output, $return);

                        // Fix syntax with PHP CS FIXER
                        if (self::PHP_CS_FIXER_ENABLE) {
                            $csfix_output = array();
                            exec("php-cs-fixer fix " . escapeshellarg($fileName) . " --fixers=" . self::PHP_CS_FIXER_FILTERS . " -vv 2>&1", $csfix_output, $return);

                            if (count($csfix_output) > 0) {
                                $this->logInfo($output, $csfix_output, ' PHP Cs Fixer ');
                                if (self::PHP_CS_FIXER_AUTOADD_GIT) {
                                    $git_output = array();
                                    exec("git add " . escapeshellarg($fileName), $git_output, $return);
                                }
                                $perfectSyntax = false;
                            }
                        }

                        // Check StopWords
                        foreach ($stopWordsPhp as $word) {
                            if (preg_match("|" . $word . "|i", file_get_contents("./" . $fileName))) {
                                $this->logError($output, sprintf("expr \"%s\" detected in %s", $word, $fileName));
                            }
                        }
                        break;

                    case "yml":
                        try {
                            Yaml::parse(file_get_contents("./" . $fileName));
                        } catch (ParseException $e) {
                            $this->logError($output, sprintf("Unable to parse the YAML file: %s", $fileName));
                        }
                        break;

                    case "xml":
                        // Check syntax with XML lint
                        $lint_output = array();
                        exec("xmllint --noout 2>&1 " . escapeshellarg($fileName), $lint_output, $return);
                        $this->logError($output, $lint_output, $return);
                        break;

                    case "js":
                        // Check StopWords
                        foreach ($stopWordsJs as $word) {
                            if (preg_match("|" . $word . "|i", file_get_contents($fileName))) {
                                $this->logError($output, sprintf("expr \"%s\" detected in %s", $word, $fileName));
                            }
                        }
                        break;
                }
                foreach ($stopWordsGit as $word) {
                    if (preg_match("|" . $word . "|i", file_get_contents($fileName))) {
                        $this->logError($output, sprintf("Git conflict marker \"%s\" detected in %s", $word, $fileName));
                    }
                }
            }
        }

        if (count($files) == 0) {
            $this->logInfo($output, "No file to check");
        } else {
            $this->analysePhpFiles($output, $phpFiles, $perfectSyntax);
            $this->logSuccess($output, $cpt, $perfectSyntax);
        }

        exit(0);
    }

    protected function analysePhpFiles($output, $phpFiles, &$perfectSyntax)
    {
        if (count($phpFiles) == 0) {
            return false;
        }

        // PHP Mess Detector
        if (self::PHP_MD_ENABLE) {
            $phpmd_output = array();
            exec("phpmd " . escapeshellarg(implode(",", $phpFiles)) . " text " . self::PHP_MD_RULESET, $phpmd_output, $return);
            if (count($phpmd_output) > 1) {
                $this->logInfo($output, array_slice($phpmd_output, 1), ' PHP Mess Detector ');
                $perfectSyntax = false;
            }
        }

        // Check Copy-paste with PHP CPD
        if (self::PHP_CPD_ENABLE) {
            $cpd_output = array();
            exec("phpcpd --min-lines " . self::PHP_CPD_MIN_LINES . " --min-tokens " . self::PHP_CPD_MIN_TOKENS . " " . implode(" ", $phpFiles), $cpd_output, $return);
            if (isset($cpd_output)) {
                $resultcpd = array();
                preg_match("|([0-9]{1,2}\.[0-9]{1,2}%)|i", implode("\n", $cpd_output), $resultcpd);
                if ($resultcpd[1] != '0.00%') {
                    $this->logInfo($output, array_slice($cpd_output, 1, -2), ' PHP Copy/Paste Detector ');
                    $perfectSyntax = false;
                }
            }
        }
    }

    protected function logError($output, $lint_output, $return = -1)
    {
        if ($return != 0) {
            if (self::WITH_PICS) {
                $this->asciImg($output);
            }
            $formattedBlock = $this->formatter->formatBlock($lint_output, 'error');
            $output->writeln($formattedBlock);
            $errorMsg = array(" Commit Rejected ", " To commit anyway, use --no-verify ");
            $formattedBlock = $this->formatter->formatBlock($errorMsg, 'error');
            $output->writeln($formattedBlock);
            exit(1);
        }
    }

    protected function logInfo($output, $lint_output, $title = '')
    {
        if ($title != '') {
            $output->writeln("\n" . '<bg=yellow>' . $title . '</bg=yellow>' . "\n");
        }
        $formattedBlock = $this->formatter->formatBlock($lint_output, 'comment');
        $output->writeln($formattedBlock);

    }

    protected function logSuccess($output, $cpt, $perfect = false)
    {
        if (self::WITH_PICS) {
            if ($perfect) {
                $this->asciImgPerfect($output);
            } else {
                $this->asciImgSuccess($output);
            }
        }

        $message = sprintf(" %s : %d checked file(s) ", ($perfect ? 'Perfect Commit' : 'Commit accepted') , $cpt);
        $output->writeln("\n" . '<bg=green>' . $message . '</bg=green>' . "\n");
    }

    protected function asciImg($output)
    {
        $output->writeln(
            "<fg=red>                _____
                 /     \
                | () () |
                 \  ^  /
                  |||||
                  |||||
             </fg=red>"
        );
    }

    protected function asciImgPerfect($output)
    {
        $output->writeln(
            "<fg=green>
                _.._..,_,_
               (          )
                ]~,\"-.-~~[
              .=])' (;  ([
              | ]:: '    [  Perfect Commit !!!
              '=]): .)  ([
                |:: '    |
                 ~~----~~
                </fg=green>"
        );
    }

    protected function asciImgSuccess($output)
    {
        $output->writeln(
            "<fg=green>
                             | | / /
                           \         /
                          \__   ____  /
                         /   \ /    \  |
                        /     |      \ \
                       /     /        | |
                      |     |         | |
                      /   () | ()     | |
                      |    __|__      | |
                     _|___/___  \___  | |
               __----         ----__\---\_
              /                        __ |
              \____-------------______/  \
                       /    /  /      / _/
                      /     \ /      / /
                     /       $      / /
                    /              / /
                   |              | /
                   \______________//
                      \________/
                  </fg=green>"
        );
    }

}