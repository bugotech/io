<?php namespace Bugotech\IO\Ofx;

use Carbon\Carbon;

class Transacao
{
    const DEBITO = 'DEBIT';
    const CREDITO = 'CREDIT';

    /**
     * Tipo da transacao.
     *
     * @var string
     */
    public $tipo = '';

    /**
     * Valor da transacao.
     *
     * @var float
     */
    public $valor = 0.00;

    /**
     * Descricao da transacao.
     *
     * @var string
     */
    public $descricao = '';

    /**
     * Data da transacao.
     *
     * @var \DateTime
     */
    public $data;

    /**
     * CheckNum.
     *
     * @var string
     */
    public $checkNum = '';

    /**
     * Id da transacao.
     *
     * @var string
     */
    public $transacaoId = '';

    /**
     * Transação constructor.
     */
    public function __construct()
    {
        $this->data = new Carbon();
    }
}