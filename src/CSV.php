<?php namespace Bugotech\IO;

class CSV extends \Illuminate\Support\Collection
{
    protected $fileName = '';
    protected $cols = [];
    protected $delimiter = ';';
    protected $maxLength = 0;
    protected $utf8 = false;
    protected $files;

    public function __construct($withHeader = true, $fileName = false, $delimiter = false, $maxLength = false, $utf8 = false)
    {
        parent::__construct();

        $this->delimiter = ($delimiter !== false) ? $delimiter : $this->delimiter;
        $this->maxLength = ($maxLength !== false) ? $maxLength : $this->maxLength;
        $this->utf8 = $utf8;
        $this->files = new Filesystem();

        if ($fileName !== false) {
            $this->load($fileName, $withHeader);
        }
    }

    /**
     * Carregar arquivo CSV.
     *
     * @param $fileName
     * @param bool $withHeader
     */
    public function load($fileName, $withHeader = true)
    {
        if ($this->files->exists($fileName) != true) {
            error('File %s not found', $fileName);
        }

        $this->fileName = $fileName;
        $this->cols = [];
        $this->items = [];

        // Carregar arquivo
        $hnd = fopen($this->fileName, 'r');
        while ($data = fgetcsv($hnd, $this->maxLength, $this->delimiter)) {
            // Primeira linha
            if ((count($this->items) == 0) && (count($this->cols) == 0)) {
                $this->cols = ($withHeader ? $data : array_keys($data));
                if ($withHeader != true) {
                    $this->items[] = $this->make_row($data);
                }
            } else { // Demais linhas
                $this->items[] = $this->make_row($data);
            }
        }
        fclose($hnd);
    }

    /**
     * Preparar linha CSV.
     *
     * @param $data
     *
     * @return mixed
     */
    protected function make_row($data)
    {
        $line = [];
        foreach ($this->cols as $i => $key) {
            $value = (array_key_exists($i, $data) ? $data[$i] : '');
            $value = ($this->utf8 ? utf8_decode($value) : $value);
            $line[$key] = $value;
        }

        return $line;
    }
}