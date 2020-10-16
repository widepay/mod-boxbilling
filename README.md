#  Módulo BoxBilling para Wide Pay
Módulo desenvolvido para integração entre o sistema BoxBilling e Wide Pay. Com o módulo é possível gerar cobrança para pagamento e liquidação automática pelo Wide Pay após o recebimento.

* **Versão atual:** 1.0.0
* **Versão BoxBilling Testada:** 4.21
* **Acesso Wide Pay**: [Abrir Link](https://www.widepay.com/acessar)
* **API Wide Pay**: [Abrir Link](https://widepay.github.io/api/index.html)
* **Módulos Wide Pay**: [Abrir Link](https://widepay.github.io/api/modulos.html)

# Instalação Plugin

1. Para a instalação do plugin realize o download do zip pelo link: https://github.com/widepay/mod-boxbilling
2. Após o download concluído, você deve extrair os arquivos e mesclar com as pastas do seu projeto BoxBilling.
3. É preciso ativar o plugin, para isso acesse o menu: BoxBilling -> Configuration -> Payment Gateways -> New Payment Gateway-> Wide Pay -> Adicionar.

# Configuração do Plugin
Lembre-se que para esta etapa, o plugin deve estar instalado e ativado no BoxBilling.

A configuração do Plugin Wide Pay pode ser encontrada no menu: BoxBilling -> Configuration -> Payment Gateways -> Wide Pay -> Configurar.


|Campo|Obrigatório|Descrição|
|--- |--- |--- |
|Titulo|**Sim**|Nome que será exibido na tela de pagamento|]
|ID da Carteira Wide Pay |**Sim** |Preencha este campo com o ID da carteira que deseja receber os pagamentos do sistema. O ID de sua carteira estará presente neste link: https://www.widepay.com/conta/configuracoes/carteiras|
|Token da Carteira Wide Pay|**Sim**|Preencha com o token referente a sua carteira escolhida no campo acima. Clique no botão: "Integrações" na página do Wide Pay, será exibido o Token|
|Tipo da Taxa de Variação|Não|Modifique o valor final do recebimento. Configure aqui um desconto ou acrescimo na venda.|
|Taxa de Variação|Não|O campo acima "Tipo de Taxa de Variação" será aplicado de acordo com este campo. Será adicionado um novo item na cobrança do Wide Pay. Esse item será possível verificar apenas na tela de pagamento do Wide Pay.|
|Acréscimo de Dias no Vencimento|Não|Número em dias para o vencimento do Boleto.|
|Configuração de Multa|Não|Configuração de multa após o vencimento. Valor em porcentagem|
|Configuração de Juros|Não|Configuração de juros após o vencimento. Valor em porcentagem|
|Forma de Recebimento|Não|Selecione entre Boleto, Cartão|


A opção enabled precisa estar ativada para aparecer a opção Wide Pay no carrinho de compras.
