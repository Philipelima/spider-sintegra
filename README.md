# Spider Sintegra

![PHP](https://img.shields.io/static/v1?label=PHP&labelColor=07a0f8&message=8.2.12+CLI&color=000000&logo=PHP&logoColor=ffffff&style=flat-square)
![CURL](https://img.shields.io/static/v1?label=CURL&labelColor=eb3c3c&message=+&color=000000&logo=CURL&logoColor=ffffff&style=flat-square)

Em um breve resumo, um spider é um sistema para captura de informações em sites da internet.

O spider sintegra tem o propósito de capturar informações sobre inscrições estaduais cadastrados no estado do Paraná.

Para rodar esse projeto em sua máquina, basta clonar este repositório da seguinte forma: 

~~~bash
    git clone https://github.com/Philipelima/spider-sintegra.git
~~~


Antes de rodar esse script, certifique-se que algumas extenções do php estejam habilitadas:

 * <b>curl</b>
 * <b>iconv</b>
 * <b>mbstring</b>

Você pode verificar isso usando o seguinte comando no php CLI:

~~~bash
    php -m
~~~

Caso elas não apareçam na listagem, basta habilita-las no arquivo <b>php.ini</b>.

### Utilização do Spider


1. No diretório do projeto, digite o seguinte comando: 

~~~bash
    php ./SpiderTest.php
~~~


<br>

<legend>Se toda configuração estiver okay, seguinte mensagem irá aparecer para você:</legend>
<img src="./.github/message_1.png" alt="Digite o cnpj">

<br>

2. Informe um CNPJ ou Inscrição Estadual da empresa que você deseja consultar (ela precisa estar cadastrado no sintegra do Paraná):
<center>
<legend>Por CNPJ:</legend>
    <img src="./.github/message_2.png" alt="Digite o cnpj">
</center>
<br>
<center>
<legend>Por Inscrição Estadual:</legend>
<img src="./.github/message_2_ie.png" alt="Digite o cnpj">
</center>
<br>

O spider irá realizar o download do captcha solicitado pelo sintegra, e lhe mostrar logo em seguida

<img src="./.github/message_3.png" alt="Digite o cnpj">

<br>

3. Abra a imagem que se encontra no caminho mostrado a cima e digite o texto apresentado na imagem:

<center>
    <img src="./.github/captcha_exemple.png" alt="Digite o cnpj">
</center>

4. Se tudo ocorrer bem, em sua tela aparecerá um array multidimensional com informações das inscrições estaduais do CNPJ pesquisado:

<center>
    <img src="./.github/resultado_consulta.png" alt="Digite o cnpj">
</center>


<br><br>

### Erros

Se o captcha for digitado errado, a seguinte mensagem de erro aparecerá para você:

<center>
    <img src="./.github/captcha_error.png" alt="Digite o cnpj">
</center>

<br><br>

Caso o CNPJ digitado não for do estado do Paraná, após a resolução do captcha a seguinte mensagem de erro aparecerá para você:


<center>
    <img src="./.github/error_cnpj_nao_cadastrado.png" alt="Digite o cnpj">
</center>