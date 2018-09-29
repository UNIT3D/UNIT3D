<?php
/**
 * NOTICE OF LICENSE
 *
 * UNIT3D is open-sourced software licensed under the GNU General Public License v3.0
 * The details is bundled with this project in the file LICENSE.txt.
 *
 * @project    UNIT3D
 * @license    https://www.gnu.org/licenses/agpl-3.0.en.html/ GNU Affero General Public License v3.0
 * @author     Poppabear
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

use App\Console\ConsoleTools;

class gitUpdater extends Command
{
    use ConsoleTools;

    /**
     * The copy command
     */
    protected $copy_command = 'cp -Rfp';

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'git:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executes the commands necessary to update your website using git';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->input = new ArgvInput();
        $this->output = new ConsoleOutput();

        $this->io = new SymfonyStyle($this->input, $this->output);

        $this->info('
        ***************************
        * Git Updater v2.5 Beta   *
        ***************************
        ');

        $this->line('<fg=cyan>
        THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" 
        
        IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, 
        SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE 
        GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) EVEN IF ADVISED OF THE POSSIBILITY 
        OF SUCH DAMAGE.
        
        WITH THAT SAID YOU CAN BE GUARANTEED THAT YOUR DATABASE WILL NOT BE ALTERED.
        
        <fg=red>BY PROCEEDING YOU AGREE TO THE ABOVE DISCLAIMER! USE AT YOUR OWN RISK!</>
        </>');

        if (!$this->io->confirm('Would you like to proceed', false)) {
            $this->line('<fg=red>Aborted ...</>');
            die();
        }

        $this->io->writeln('
        Press CTRL + C ANYTIME to abort! Aborting can lead to unexpected results!
        ');

        sleep(1);

        $this->update();

        $this->white('Please report any errors or issues.');

        $this->done();
    }

    private function update()
    {
        $updating = $this->checkForUpdates();

        if (count($updating) > 0) {
            $this->alertDanger('Found Updates');

            $this->cyan('Files that need updated:');
            $this->io->listing($updating);

            if ($this->io->confirm('Start the update process', false)) {
                $this->call('down', [
                    '--message' => "Currently Updating",
                    '--retry' => '300'
                ]);

                $this->process("git add .");

                $paths = $this->paths();

                $this->backup($paths);

                $this->header('Reseting Repository');

                $this->commands([
                    'git fetch origin',
                    'git reset --hard origin/master',
                ]);

                $this->restore($paths);

                $conflicts = array_intersect($updating, $paths);
                if (count($conflicts) > 0) {
                    $this->red('There are some files that was not updated because because of conflicts.');
                    $this->red('We will walk you through updating these files now.');

                    $this->manualUpdate($conflicts);
                }

                if ($this->io->confirm('Run new migrations (php artisan migrate)', false)) {
                    $this->migrations();
                }

                if ($this->io->confirm('Compile assets (npm run prod)', false)) {
                    $this->compile();
                }

                if ($this->io->confirm('Install new packages (composer install)', false)) {
                    $this->composer();
                }

                $this->clear();

                $this->call('up');
            } else {
                $this->alertDanger('Aborted Update');
                die();
            }
        } else {
            $this->alertSuccess("No Available Updates Found");
        }
    }

    private function checkForUpdates()
    {
        $this->header("Checking For Updates");

        $this->process('git fetch origin');
        $process = $this->process('git diff ..origin/master --name-only');
        $updating = array_filter(explode("\n", $process->getOutput()), 'strlen');

        $this->done();

        return $updating;
    }

    private function manualUpdate($updating)
    {
        $this->alertInfo("Manual Update");
        $this->red("Updating will cause you to LOSE any changes you might have made to the file!");

        foreach ($updating as $file) {
            if ($this->io->confirm("Update $file", false)) {
                $this->updateFile($file);
            }
        }

        $this->done();
    }

    private function updateFile($file)
    {
        $this->process("git checkout origin/master -- $file");
    }

    private function backup(array $paths)
    {
        $this->header('Backing Up Files');

        $this->commands([
            'rm -rf ' . storage_path('gitupdate'),
            'mkdir ' . storage_path('gitupdate')
        ], true);

        foreach ($paths as $path) {
            $this->validatePath($path);
            $this->createBackupPath($path);
            $this->process($this->copy_command . ' ' . base_path($path) . ' ' . storage_path('gitupdate') . '/' . $path);
        }

        $this->done();
    }

    private function restore(array $paths)
    {
        $this->header('Restoring Backups');

        foreach ($paths as $path) {
            $to = str_replace_last('/.', '', base_path(dirname($path)));
            $from = storage_path('gitupdate') . '/' . $path;

            if (is_dir($from)) {
                $to .= '/'.basename($from).'/';
                $from .= '/*';
            }

            $this->process("$this->copy_command $from $to");
        }

        $this->commands([
            'git add .',
            'git checkout origin/master -- package-lock.json',
            'git checkout origin/master -- composer.lock'
        ]);
    }

    private function composer()
    {
        $this->header("Installing Composer Packages");

        $this->commands([
            'composer install',
            'composer dump-autoload'
        ]);

        $this->done();
    }

    private function compile()
    {
        $this->header('Compiling Assets ...');

        $this->commands([
            'rm -rf node_modules',
            'npm install',
            'npm run prod'
        ]);

        $this->done();
    }

    private function clear()
    {
        $this->header("Clearing Cache");
        $this->call('clear:all');
        $this->done();
    }

    private function migrations()
    {
        $this->header("Running New Migrations");
        $this->call('migrate');
        $this->done();
    }

    private function validatePath($path)
    {
        if (!is_file(base_path($path)) && !is_dir(base_path($path))) {
            $this->red("The path '$path' is invalid");
            $this->call('up');
            die();
        }
    }

    private function createBackupPath($path)
    {
        if (!is_dir(storage_path("gitupdate/$path")) && !is_file(base_path($path))) {
            mkdir(storage_path("gitupdate/$path"), 0775, true);
        } elseif (is_file(base_path($path)) && dirname($path) !== '.') {
            $path = dirname($path);
            if (!is_dir(storage_path("gitupdate/$path"))) {
                mkdir(storage_path("gitupdate/$path"), 0775, true);
            }
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [

        ];
    }

    /**
     * @return array
     */
    private function paths()
    {
        $p = $this->process("git diff master --name-only");
        $paths = array_filter(explode("\n", $p->getOutput()), 'strlen');

        $additional = [
            '.env',
            'laravel-echo-server.json',
            'public/files',
        ];

        return array_merge($paths, $additional);
    }
}
