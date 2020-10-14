<?php
/**
 * BoxBilling
 *
 * @copyright BoxBilling, Inc (http://www.boxbilling.com)
 * @license   Apache-2.0
 *
 * Copyright BoxBilling, Inc
 * This source file is subject to the Apache-2.0 License that is bundled
 * with this source code in the file LICENSE
 */

class Payment_Adapter_WidePay
{
    private $config = array();

    public function __construct($config)
    {
        $this->config = $config;

        if (!function_exists('curl_exec')) {
            throw new Payment_Exception('PHP Curl extension must be enabled in order to use Wide Pay gateway');
        }
    }

    public static function getConfig()
    {
        return array(
            'supports_one_time_payments' => true,
            'description' => "<div style=\"margin-left: 0px;\">
                                <strong>Sobre</strong>: <a href=\"https://www.widepay.com/\" target=\"_blank\">https://www.widepay.com/</a><br>
                                <strong>Acessar</strong>: <a href=\"https://www.widepay.com/acessar\" target=\"_blank\">https://www.widepay.com/acessar</a><br>
                                <strong>Carteiras</strong>: <a
                                        href=\"https://www.widepay.com/conta/configuracoes/carteiras\" target=\"_blank\">https://www.widepay.com/conta/configuracoes/carteiras</a><br>
                                <strong>Guia</strong>: <a href=\"https://api.widepay.com\" target=\"_blank\">https://api.widepay.com</a><br>
                                <strong>Ajuda</strong>: <a href=\"https://www.widepay.com/faq\" target=\"_blank\">https://www.widepay.com/faq</a>
                                <hr>
                            </div>",
            'form' => array(
                'WIDE_PAY_WALLET_ID' => array('text', array(
                    'label' => 'ID da Carteira Wide Pay',
                    'description' => 'Preencha este campo com o ID da carteira que deseja receber os pagamentos do sistema. O ID de sua carteira estará presente neste link: https://www.widepay.com/conta/configuracoes/carteiras',
                    'validators' => array('notempty'),
                )),
                'WIDE_PAY_WALLET_TOKEN' => array('text', array(
                    'label' => 'Token da Carteira Wide Pay',
                    'description' => 'Preencha com o token referente a sua carteira escolhida no campo acima. Clique no botão: "Integrações" na página do Wide Pay, será exibido o Token',
                    'validators' => array('notempty'),
                )),
                'WIDE_PAY_TAX_TYPE' => array('select', array(
                    'multiOptions' => array(
                        '0' => 'Sem alteração',
                        '1' => 'Acrécimo em %',
                        '2' => 'Acrécimo valor fixo em R$',
                        '3' => 'Desconto em %',
                        '4' => 'Desconto valor fixo em R$',
                    ),
                    'label' => 'Tipo da Taxa de Variação',
                    'description' => 'Modifique o valor final do recebimento. Configure aqui um desconto ou acrescimo na venda.',
                )),
                'WIDE_PAY_TAX_VARIATION' => array('text', array(
                    'label' => 'Taxa de Variação',
                    'description' => 'O campo acima "Tipo de Taxa de Variação" será aplicado de acordo com este campo. Será adicionado um novo item na cobrança do Wide Pay. Esse item será possível verificar apenas na tela de pagamento do Wide Pay.',
                )),
                'WIDE_PAY_VALIDADE' => array('text', array(
                    'label' => 'Acréscimo de Dias no Vencimento',
                    'description' => 'Prazo de validade em dias para o Boleto.',
                    'validators' => array('notempty'),
                )),
                'WIDE_PAY_FINE' => array('text', array(
                    'label' => 'Configuração de Multa',
                    'description' => 'Configuração de multa após o vencimento',
                )),
                'WIDE_PAY_INTEREST' => array('text', array(
                    'label' => 'Configuração de Juros',
                    'description' => 'Configuração de juros após o vencimento',
                )),
                'WIDE_PAY_WAY' => array('select', array(
                    'multiOptions' => array(
                        'boleto_cartao' => 'Boleto e Cartão',
                        'boleto' => 'Boleto',
                        'cartao' => 'Cartão',
                    ),
                    'label' => 'Forma de Recebimento',
                    'description' => 'Selecione uma opção.',
                    'validators' => array('notempty'),
                )),
            ),
        );
    }

    public function recurrentPayment(Payment_Invoice $invoice)
    {
        throw new Payment_Exception('No momento, esta opção não está disponível');
    }

    public function getHtml($api_admin, $invoice_id, $subscription)
    {
        $invoice = $api_admin->invoice_get(array('id' => $invoice_id));


        $buyer = $invoice['buyer'];
        $nome_cliente = $buyer['company'];
        $firstname = $buyer['first_name'];
        $lastname = $buyer['last_name'];
        if (is_null($nome_cliente) || trim($nome_cliente) == '' || $nome_cliente == ' ') {
            $nome_cliente = $firstname . ' ' . $lastname;
        }


        //custom
        $tax = $this->config['WIDE_PAY_TAX_VARIATION'];
        $tax_type = $this->config['WIDE_PAY_TAX_TYPE'];


        //produtos
        $items = array();
        $items[1]['descricao'] = 'Valor total da fatura';
        $items[1]['valor'] = number_format($invoice['total'], 2, '.', '');
        $items[1]['quantidade'] = 1;
        $variableTax = $this->getVariableTax($tax, $tax_type, $invoice['total']);
        if (isset($variableTax)) {
            list($description, $total) = $variableTax;
            $items[2]['descricao'] = $description;
            $items[2]['valor'] = $total;
            $items[2]['quantidade'] = 1;
        }


        //////------


        $invoiceDuedate = new DateTime(date('Y-m-d'));
        $invoiceDuedate->modify('+' . intval($this->config['WIDE_PAY_VALIDADE']) . ' day');
        $invoiceDuedate = $invoiceDuedate->format('Y-m-d');


        $widepayData = array(
            'forma' => $this->widepay_get_formatted_way(trim($this->config['WIDE_PAY_WAY'])),
            'referencia' => $invoice_id,
            'notificacao' => $this->config['notify_url'],
            'vencimento' => $invoiceDuedate,
            'cliente' => $nome_cliente,
            'email' => $buyer['email'],
            'enviar' => 'E-mail',
            'endereco' => array(
                'rua' => $buyer['address'],
                'complemento' => '',
                'cep' => preg_replace('/\D/', '', $buyer['zip']),
                'estado' => $this->change_uf_widepay($buyer['state']),
                'cidade' => $buyer['city']
            ),
            'itens' => $items,
            'boleto' => array(
                'gerar' => 'Nao',
                'desconto' => 0,
                'multa' => doubleval($this->config['WIDE_PAY_FINE']),
                'juros' => doubleval($this->config['WIDE_PAY_INTEREST'])
            )
        );


        $url = $this->config['notify_url'];
        $form = '';
        $form .= '<form name="payment_form" action="' . $url . '" method="post">' . PHP_EOL;
        $form .= '<input type="hidden" name="acao" value="gerar">' . PHP_EOL;
        $form .= '<input type="hidden" name="dados" value="' . urlencode(serialize($widepayData)) . '">' . PHP_EOL;
        $form .= '<label for="cpf_cnpj">CPF ou CNPJ:' . PHP_EOL;
        $form .= '<input id="cpf_cnpj" type="text" name="cpf_cnpj" value="">' . PHP_EOL;
        $form .= '<input class="bb-button bb-button-submit" type="submit" value="Pagar com Wide Pay" id="payment_button"></button>' . PHP_EOL;
        $form .= '</form>' . PHP_EOL . PHP_EOL;


        return $form;

    }

    private function getVariableTax($tax, $taxType, $total)
    {
        //Formatação para calculo ou exibição na descrição
        $widepayTaxDouble = number_format((double)$tax, 2, '.', '');
        $widepayTaxReal = number_format((double)$tax, 2, ',', '');
        // ['Description', 'Value'] || Null

        if ($taxType == 1) {//Acrécimo em Porcentagem
            return array(
                'Referente a taxa adicional de ' . $widepayTaxReal . '%',
                round((((double)$widepayTaxDouble / 100) * $total), 2));
        } elseif ($taxType == 2) {//Acrécimo valor Fixo
            return array(
                'Referente a taxa adicional de R$' . $widepayTaxReal,
                ((double)$widepayTaxDouble));
        } elseif ($taxType == 3) {//Desconto em Porcentagem
            return array(
                'Item referente ao desconto: ' . $widepayTaxReal . '%',
                round((((double)$widepayTaxDouble / 100) * $total), 2) * (-1));
        } elseif ($taxType == 4) {//Desconto valor Fixo
            return array(
                'Item referente ao desconto: R$' . $widepayTaxReal,
                $widepayTaxDouble * (-1));
        }
        return null;
    }

    public function processTransaction($api_admin, $id, $data, $gateway_id)
    {
        if (isset($data['post']['acao']) && $data['post']['acao'] == 'gerar') {
            $widepayData = unserialize(urldecode($data['post']['dados']));
            $cpf_cnpj = $data['post']['cpf_cnpj'];
            list($widepayCpf, $widepayCnpj, $widepayPessoa) = $this->getFiscal($cpf_cnpj);
            $widepayData['cpf'] = $widepayCpf;
            $widepayData['cnpj'] = $widepayCnpj;
            $widepayData['pessoa'] = $widepayPessoa;
            $response = $this->api(intval($this->config['WIDE_PAY_WALLET_ID']), trim($this->config['WIDE_PAY_WALLET_TOKEN']), 'recebimentos/cobrancas/adicionar', $widepayData);

            if (!$response->sucesso) {
                $validacao = '';

                if ($response->erro) {
                    echo 'Wide Pay: Erro (' . $response->erro . ')' . '<br>';
                }

                if (isset($response->validacao)) {
                    foreach ($response->validacao as $item) {
                        $validacao .= '- ' . strtoupper($item['id']) . ': ' . $item['erro'] . '<br>';
                    }
                    echo 'Wide Pay: Erro de validação (' . $validacao . ')';
                }

            } else {
                echo "Redirecionando... " . $response->link;
                echo "<script>
                        window.location.href = \"" . $response->link . "\"
                     </script>";
            }
        } else {/// Notificação
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["notificacao"])) {
                $notificacao = $this->api(intval($this->config['WIDE_PAY_WALLET_ID']), trim($this->config['WIDE_PAY_WALLET_TOKEN']), 'recebimentos/cobrancas/notificacao', array(
                    'id' => $_POST["notificacao"] // ID da notificação recebido do Wide Pay via POST
                ));
                if ($notificacao->sucesso) {
                    $order_id = (int)$notificacao->cobranca['referencia'];
                    $transactionID = $notificacao->cobranca['id'];
                    $status = $notificacao->cobranca['status'];
                    if ($status == 'Baixado' || $status == 'Recebido' || $status == 'Recebido manualmente') {
                        $d = array(
                            'id' => $order_id,
                        );
                        $api_admin->invoice_mark_as_paid($d);

                        $today = new DateTime(date('Y-m-d'));
                        $today = $today->format('Y-m-d');

                        $d = array(
                            'id' => $order_id,
                            'gateway_id' => $gateway_id,
                            'amount' => $notificacao->cobranca['recebido'],
                            'status' => 'paid',
                            'paid_at' => isset($notificacao->cobranca['recebimento']) ? $notificacao->cobranca['recebimento'] : $today,
                        );
                        $api_admin->invoice_update($d);
                    }


                } else {
                    echo $notificacao->erro; // Erro
                }
            }
        }
    }

    private function getFiscal($cpf_cnpj)
    {
        $cpf_cnpj = preg_replace('/\D/', '', $cpf_cnpj);
        // [CPF, CNPJ, FISICA/JURIDICA]
        if (strlen($cpf_cnpj) == 11) {
            return array($cpf_cnpj, '', 'Física');
        } else {
            return array('', $cpf_cnpj, 'Jurídica');
        }
    }

    private function widepay_get_formatted_way($way)
    {
        $key_value = array(
            'cartao' => 'Cartão',
            'boleto' => 'Boleto',
            'boleto_cartao' => 'Cartão,Boleto',

        );
        return $key_value[$way];
    }

    function change_uf_widepay($state)
    {
        $state = strtoupper($state);
        $accents = array(
            'A' => array("Á", "À", "Â", "Ã", "Ä", "á", "à", "â", "ã", "ä",),
            'E' => array("É", "È", "Ê", "Ë", "é", "è", "ê", "ë",),
            'I' => array("Í", "Ì", "Î", "Ï", "í", "ì", "î", "ï",),
            'O' => array("Ó", "Ò", "Ô", "Õ", "Ö", "ó", "ò", "ô", "õ", "ö",),
            'U' => array("Ú", "Ù", "Û", "Ü", "ú", "ù", "û", "ü",),
            'misc' => array(" ", ".", ",", ":", ";", "<", ">", "\\", "/",
                "\"", "'", "|", "?", "!", "@", "#", "$", "%", "&", "*", "(",
                ")", "[", "]", "{", "}", "´", "`", "~", "^", "¨", "-", "_",
                "=", "+", "1", "2", "3", "4", "5", "6", "7", "8", "9", "0",),
        );
        $state = str_ireplace($accents['A'], "A", $state);
        $state = str_ireplace($accents['E'], "E", $state);
        $state = str_ireplace($accents['I'], "I", $state);
        $state = str_ireplace($accents['O'], "O", $state);
        $state = str_ireplace($accents['U'], "U", $state);
        $state = str_ireplace($accents['misc'], "", $state);
        switch ($state) {
            case "ACRE":
                $state = "AC";
                break;
            case "ALAGOAS":
                $state = "AL";
                break;
            case "AMAPA":
                $state = "AP";
                break;
            case "AMAZONAS":
                $state = "AM";
                break;
            case "BAHIA":
                $state = "BA";
                break;
            case "CEARA":
                $state = "CE";
                break;
            case "DISTRITOFEDERAL":
                $state = "DF";
                break;
            case "ESPIRITOSANTO":
                $state = "ES";
                break;
            case "GOIAS":
                $state = "GO";
                break;
            case "MARANHAO":
                $state = "MA";
                break;
            case "MATOGROSSO":
                $state = "MT";
                break;
            case "MATOGROSSODOSUL":
                $state = "MS";
                break;
            case "MINASGERAIS":
                $state = "MG";
                break;
            case "PARA":
                $state = "PA";
                break;
            case "PARAIBA":
                $state = "PB";
                break;
            case "PARANA":
                $state = "PR";
                break;
            case "PERNAMBUCO":
                $state = "PE";
                break;
            case "PIAUI":
                $state = "PI";
                break;
            case "RIODEJANEIRO":
                $state = "RJ";
                break;
            case "RIOGRANDEDONORTE":
                $state = "RN";
                break;
            case "RIOGRANDEDOSUL":
                $state = "RS";
                break;
            case "RONDONIA":
                $state = "RO";
                break;
            case "RORAIMA":
                $state = "RR";
                break;
            case "SANTACATARINA":
                $state = "SC";
                break;
            case "SAOPAULO":
                $state = "SP";
                break;
            case "SERGIPE":
                $state = "SE";
                break;
            case "TOCANTINS":
                $state = "TO";
                break;
        }
        return $state;
    }


    function api($wallet, $token, $local, $params = array())
    {

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.widepay.com/v1/' . trim($local, '/'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, trim($wallet) . ':' . trim($token));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('WP-API: SDK-PHP'));
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_SSLVERSION, 1);
        $exec = curl_exec($curl);
        curl_close($curl);
        if ($exec) {
            $requisicao = json_decode($exec, true);
            if (!is_array($requisicao)) {
                $requisicao = array(
                    'sucesso' => false,
                    'erro' => 'Não foi possível tratar o retorno.'
                );
                if ($exec) {
                    $requisicao['retorno'] = $exec;
                }
            }
        } else {
            $requisicao = array(
                'sucesso' => false,
                'erro' => 'Sem comunicação com o servidor.'
            );
        }

        return (object)$requisicao;
    }
}