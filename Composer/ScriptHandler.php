<?php

namespace Symfony\Cmf\Bundle\CreateBundle\Composer;

/**
 * A hack to work around the missing support for js assets in composer
 *
 * @see    http://groups.google.com/group/composer-dev/browse_thread/thread/e9e2f7d919aadfec
 *
 * @author David Buchmann
 */
class ScriptHandler {
    /**
     * @param \Composer\Script\CommandEvent $event
     * @throws \RuntimeException
     */
    public static function initSubmodules ($event) {
        $status = null;
        $output = array();
        $dir = getcwd();
        chdir(__DIR__ . DIRECTORY_SEPARATOR . '..');
        if (file_exists(__DIR__ . '/../.git')) {
            exec('git submodule sync', $output, $status);
            if ($status) {
                chdir($dir);
                throw new \RuntimeException("Running 'git submodule sync failed' with $status");
            }
            exec('git submodule update --init --recursive', $output, $status);
            if ($status) {
                chdir($dir);
                throw new \RuntimeException("Running 'git submodule --init --recursive' failed with $status");
            }
        } else {
            $modules = parse_ini_file(__DIR__ . '/../.gitmodules', true);
            foreach ($modules as $module) {
                $path = $module['path'];
                $url = $module['url'];

                file_exists($path) and rmdir($path);
                exec("git clone --recursive -- $url $path", $output, $status);
                if ($status) {
                    chdir($dir);
                    throw new \RuntimeException("Running 'git clone -- $url $path' failed with $status");
                }
            }
        }
        chdir($dir);
    }

    public static function downloadCkeditor($event)
    {
        $extra = $event->getComposer()->getPackage()->getExtra();
        $event->getIO()->write("<info>Download or update ckeditor</info>");

        // directory where the repository should be clone into
        if (isset($extra['ckeditor-directory'])) {
            $directory = getcwd() . '/' . $extra['ckeditor-directory'];
        } else {
            $directory = __DIR__ . '/../Resources/public/vendor/ckeditor';
        }

        // git repository
        if (isset($extra['ckeditor-repository'])) {
            $repository = $extra['ckeditor-repository'];
        } else {
            $repository = 'https://github.com/ckeditor/ckeditor-releases.git';
        }

        // commit id
        if (isset($extra['ckeditor-commit'])) {
            $commit = $extra['ckeditor-commit'];
        } else {
            $commit = 'bba29309f93a1ace1e2e3a3bd086025975abbad0';
        }

        ScriptHandler::gitSynchronize($directory, $repository, $commit);
    }

    /**
     * @param string $directory The directory where the repository should be clone into
     * @param string $repository The git repository
     * @param string $commitId The commit id
     */
    public static function gitSynchronize($directory, $repository, $commitId)
    {
        $currentDirectory = getcwd();
        $parentDirectory = dirname($directory);
        $projectDirectory = basename($directory);

        $status = null;
        $output = array();
        chdir($parentDirectory);

        if (is_dir($projectDirectory)) {
            chdir($projectDirectory);
            exec("git remote update", $output, $status);
            if ($status) {
                die("Running git pull $repository failed with $status\n");
            }
        } else {
            exec("git clone $repository $projectDirectory -q", $output, $status);
            if ($status) {
                die("Running git clone $repository failed with $status\n");
            }
            chdir($projectDirectory);
        }

        exec("git checkout $commitId -q", $output, $status);
        if ($status) {
            die("Running git clone $repository failed with $status\n");
        }

        chdir($currentDirectory);
    }
}
