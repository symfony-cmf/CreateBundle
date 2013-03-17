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
}
