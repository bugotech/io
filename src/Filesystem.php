<?php namespace Bugotech\IO;

class Filesystem extends \Illuminate\Filesystem\Filesystem
{
    /**
     * Get base path of file or directory.
     *
     * @return string
     */
    public function path($filename)
    {
        $info = pathinfo($filename);

        return $info['dirname'];
    }

    /**
     * Remove path base of filename.
     *
     * @param $filename
     *
     * @return string
     */
    public function pathInBase($filename)
    {
        $filename = str_replace('\\', '/', $filename);
        $path_root = str_replace('\\', '/', base_path() . '/');

        return str_replace($path_root, '', $filename);
    }

    /**
     * Alias of makeDirectory.
     *
     * @return bool
     */
    public function force($path, $mode = 0777, $recursive = true)
    {
        if ($this->exists($path)) {
            return true;
        }

        return $this->makeDirectory($path, $mode, $recursive);
    }

    /**
     * Combine two paths.
     *
     * @return string
     */
    public function combine($path1, $path2, $div = '/')
    {
        $path1 .= (($path1[strlen($path1) - 1] != $div) ? $div : '');

        return $path1 . $path2;
    }

    /**
     * Get filename with or not extension.
     *
     * @return string
     */
    public function fileName($filename, $withExt = true)
    {
        return $withExt ? pathinfo($filename, PATHINFO_BASENAME) : pathinfo($filename, PATHINFO_FILENAME);
    }

    /**
     * Rename file with option of make new filename.
     *
     * @return string
     */
    public function rename($filename, $newname, $try_another_name = false)
    {
        // Verificar se deve tentar outro nome caso ja exista
        if ($try_another_name) {
            $file_mask = preg_replace('/(.[a-zA-Z0-9]+)$/', '_%s\1', $filename);
            $contador = 1;
            while ($this->exists($newname)) {
                $newname = sprintf($file_mask, $contador);
                $contador += 1;
            }
        }

        $this->copy($filename, $newname);
        $this->delete($filename);

        return $newname;
    }

    /**
     * Synchronize from path with to path.
     *
     * @param $fromPath
     * @param $toPath
     *
     * @return bool
     */
    public function synchronize($fromPath, $toPath, $renames = [])
    {
        // Verificar se fromPath e um diretório
        if (! $this->isDirectory($fromPath)) {
            return false;
        }

        // Verificar se deve criar o toPath
        if (! $this->isDirectory($toPath)) {
            $this->makeDirectory($toPath, 0777, true);
        }

        // Copiar sincronizar estrutura
        $items = new \FilesystemIterator($fromPath, \FilesystemIterator::SKIP_DOTS);
        foreach ($items as $item) {
            $target = $toPath . '/' . $item->getBasename();

            if ($item->isDir()) {
                $path = $item->getPathname();
                if (! $this->synchronize($path, $target, $renames)) {
                    return false;
                }
            } else {
                // verificar se deve renomear
                foreach ($renames as $old => $new) {
                    $target = str_replace($old, $new, $target);
                }

                // Verificar se arquivo existe
                if ($this->exists($target)) {
                    $hash_file = md5_file($item->getPathname());
                    $hash_dest = md5_file($target);
                    if ($hash_file != $hash_dest) {
                        if (! $this->copy($item->getPathname(), $target)) {
                            return false;
                        }
                    }
                } else {
                    if (! $this->copy($item->getPathname(), $target)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Get file content.
     *
     * @param $file
     * @param array $vars
     * @return string
     * @throws \Exception
     */
    public function getContentRequire($file, $vars = [])
    {
        ob_start();
        try {
            extract($vars);

            require $file;

            return ob_get_clean();
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * Salvar arquivo Config.
     *
     * @param $group
     * @param string $environment
     */
    public function saveConfig($group, $environment = '')
    {
        // Path do arquivo
        $path = config_path();

        // Itens
        $items = config($group, []);

        // Nome do arquivo
        $file = (! $environment || ($environment == 'production'))
            ? "{$path}/{$group}.php"
            : "{$path}/{$environment}/{$group}.php";

        // Salvar arquivo
        $code = '<?php' . "\r\n\r\n";
        $code .= 'return ' . var_export($items, true) . ';';
        $this->put($file, $code);
    }
}