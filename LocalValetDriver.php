<?php

class LocalValetDriver extends ValetDriver
{
    private string $site_folder = '/public';

    /**
     * Determine if the driver serves the request.
     *
     * @param string $sitePath
     * @param string $siteName
     * @param string $uri
     */
    public function serves($sitePath, $siteName, $uri): bool
    {
        return true;
    }

    /**
     * Determine if the incoming request is for a static file.
     *
     * @param string $sitePath
     * @param string $siteName
     * @param string $uri
     */
    public function isStaticFile($sitePath, $siteName, $uri): bool|string
    {
        if (file_exists($staticFilePath = $sitePath . $this->site_folder . $uri)) {
            return $staticFilePath;
        }

        if (
            str_ends_with($uri, '.png')
            || str_ends_with($uri, '.jpg')
        ) {
            return $staticFilePath;
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     *
     * @param string $sitePath
     * @param string $siteName
     * @param string $uri
     */
    public function frontControllerPath($sitePath, $siteName, $uri): string
    {
        if (str_ends_with($uri, '.php') && file_exists($sitePath . $this->site_folder . '/' . $uri)) {
            return $sitePath . $this->site_folder . '/' . $uri;
        }

        if (str_starts_with($uri, '/achievement/')) {
            $id = explode('/', $uri)[2];
            $_GET['ID'] = $id;

            return $sitePath . $this->site_folder . '/achievementInfo.php';
        }

        if (str_starts_with($uri, '/game/')) {
            $id = explode('/', $uri)[2];
            $_GET['ID'] = $id;

            return $sitePath . $this->site_folder . '/gameInfo.php';
        }

        if (str_starts_with($uri, '/user/')) {
            $id = explode('/', $uri)[2];
            $_GET['ID'] = $id;

            return $sitePath . $this->site_folder . '/userInfo.php';
        }

        return $sitePath . $this->site_folder . '/index.php';
    }
}
