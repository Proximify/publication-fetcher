<?php

/**
 * @author    Proximify Inc <support@proximify.com>
 * @copyright Copyright (c) 2020, Proximify Inc
 * @license   MIT
 */

namespace Proximify;

/**
 * Set up Composer to install and update NPM,Yarn and PIP packages when
 * a composer packaged is installed or updated.
 */
class ForeignPackages extends CLIActions
{
    /** @var string Path to the package URL settings. */
    public const PKG_URL_PATH = 'settings/ForeignPackages.json';

    /**
     * @inheritDoc
     */
    public function runComposerAction(array $options): void
    {
        $env = $options[self::ENV_OPTIONS];
        $map = [
            'post-create-project-cmd' => 'install',
            'post-install-cmd' => 'install',
            'post-update-cmd' => 'update'
        ];

        if ($action = $map[$env['action']] ?? false) {
            $this->processPackages($action, $options);
        }
    }

    /**
     * Execute a command at a giver working directory.
     *
     * @param string $cmd The command to execute.
     * @param string|null $workingDir The working directory.
     * @param array|null $env Environment variables.
     * @return array Output array with 'code', 'out' and 'err'
     * keys representing the output code, the output text and
     * the standard error output.
     */
    public static function execute(string $cmd, string $workingDir, ?array $env = null): array
    {
        $descriptorSpec = array(
            0 => array('pipe', 'r'),  // stdin
            1 => array('pipe', 'w'),  // stdout
            2 => array('pipe', 'w'),  // stderr
        );

        $process = proc_open($cmd, $descriptorSpec, $pipes, $workingDir, $env);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $out = [
            'code' => proc_close($process),
            'out' => trim($stdout),
            'err' => trim($stderr),
        ];

        return $out;
    }

    /**
     * Find the path to an app.
     *
     * @param string $app
     * @return string|null
     */
    public static function findApp(string $app): ?string
    {
        $status = self::execute("which $app", getcwd());

        return (!$status['code']) ? $status['out'] : null;
    }

    /**
     * Apply action to all registered foreign packages.
     *
     * @see https://docs.npmjs.com/cli-documentation/cli
     * @see https://docs.npmjs.com/cli/install
     * @see https://docs.npmjs.com/cli/update.html
     *
     * Python:
     * https://pip.pypa.io/en/stable/installing/
     *
     * @param string $action
     * @return void
     */
    protected function processPackages(string $action, array $options): void
    {
        $packages = $this->getForeignPackages($action, $options);

        foreach ($packages as $path => $cmd) {
            self::echoMsg("Running '$cmd' at '$path'...");

            $status = self::execute($cmd, $path);

            if ($status['out'] || $status['err']) {
                self::echoMsg('Output: ' . $status['out']);
                self::echoMsg('Error: ' . $status['err'], ['separator' => true]);
            } else {
                self::echoMsg('Done', ['separator' => true]);
            }
        }
    }

    protected static function throwError(string $msg, array $context = []): void
    {
        if ($context) {
            $msg = "\n" . print_r($context, true);
        }

        $className = static::class;

        throw new \Exception("$className:\n$msg");
    }

    /**
     * Get the foreign packages to process.
     *
     * @param array $options
     * @return array
     */
    private function getForeignPackages(string $action, array $options): array
    {
        // Keep a map of app names to app paths
        $apps = [];

        $rootDir = $options['rootDir'] ?? getcwd();

        $json = file_get_contents($rootDir . '/' . self::PKG_URL_PATH);

        $urls = $json ? json_decode($json, true) : [];
        $valid = [];

        foreach ($urls as $path => $app) {
            if (is_array($app)) {
                $appName = $app['filename'];
                $cmd = $app['commands'][$action] ?? $action;
            } else {
                $wc = str_word_count($app);

                if (($wc == 1 && !self::findApp($app)) && ($wc != 1 && !is_file($app))) {
                    self::throwError("Cannot find command-line app named '$app'");
                }

                $appName = $app;
                $cmd = $action;
            }

            // Make an absolute path and check that it exists and it is
            // within the root directory
            $path = $rootDir . '/' . $path;

            if (is_dir($path) && strpos($path, '/..') == false) {
                $valid[$path] = "$appName $cmd";
            }
        }

        return $valid;
    }
}
