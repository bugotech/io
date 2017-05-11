<?php namespace Bugotech\IO\Ofx;

use Carbon\Carbon;

class Ofx
{
    /**
     * Headers.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Conta.
     *
     * @var Conta
     */
    public $conta;

    /**
     * Saldo total das transacoes.
     *
     * @var string
     */
    public $saldoTotal = '';

    /**
     * Data Inicial.
     *
     * @var \DateTime
     */
    public $dataInicial;

    /**
     * @var \DateTime
     */
    public $dataFinal;

    /**
     * Transacoes.
     *
     * @var array
     */
    public $transacoes = [];


    /**
     * Load an OfxFile.
     *
     * @param $ofxFile
     */
    public function load($ofxFile)
    {
        $content = file_get_contents($ofxFile);

        //Headers
        $inicio = stripos($content, '<OFX>');
        $header = trim(substr($content, 0, $inicio));
        $this->headers = $this->headers($header);

        $content = str_replace($header, '', $content);

        $dom = new \DOMDocument();
        $dom->loadXML($content);

        //Ofx
        $this->saldoTotal = $dom->getElementsByTagName('BALAMT')->item(0)->textContent;
        $this->dataInicial = $this->date($dom->getElementsByTagName('DTSTART')->item(0)->textContent);
        $this->dataFinal = $this->date($dom->getElementsByTagName('DTEND')->item(0)->textContent);

        //Conta
        $this->conta->banco = $dom->getElementsByTagName('BANKID')->item(0)->textContent;
        $this->conta->numConta = substr($dom->getElementsByTagName('ACCTID')->item(0)->textContent, 5);
        $this->conta->agencia = substr($dom->getElementsByTagName('ACCTID')->item(0)->textContent, 0, 5);


        //Transacoes
        foreach ($dom->getElementsByTagName('STMTTRN') as $item) {
            $transacao = new Transacao();

            $transacao->tipo = $item->getElementsByTagName('TRNTYPE')->item(0)->textContent;
            $transacao->valor = $item->getElementsByTagName('TRNAMT')->item(0)->textContent;
            $transacao->descricao = $item->getElementsByTagName('MEMO')->item(0)->textContent;
            $transacao->data = $this->date($item->getElementsByTagName('DTPOSTED')->item(0)->textContent);
            $transacao->checkNum = $item->getElementsByTagName('CHECKNUM')->item(0)->textContent;
            $transacao->transacaoId = $item->getElementsByTagName('FITID')->item(0)->textContent;

            $this->transacoes[] = $transacao;
        }
    }

    /**
     * Get headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Headers.
     *
     * @param $header
     * @return array
     */
    protected function headers($header)
    {
        $lines = explode("\r\n", $header);

        $headers = [];
        foreach ($lines as $line) {
            $key = explode(':', $line)[0];
            $value = explode(':', $line)[1];
            $headers[$key] = $value;
        }

        return $headers;
    }

    /**
     * Formatar o tipo datetime.
     *
     * @param $date
     * @return static
     */
    protected function date($date)
    {
        $date = str_replace('[-03:EST]', '', $date);

        return Carbon::createFromFormat('Ymdhms', $date);
    }

    /**
     * Ofx constructor.
     * @param null $ofxFile
     */
    public function __construct($ofxFile = null)
    {
        $this->conta = new Conta();
        $ofxFile != null ? $this->load($ofxFile) : null;
    }
}