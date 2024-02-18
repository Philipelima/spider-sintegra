<?php 
declare(strict_types=1);

class Spider {
    

    private const  DEFAULT_CURL_CONFIG = [

        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_COOKIEFILE     => './cookies/cookies.txt',
        CURLOPT_COOKIEJAR      => './cookies/cookies.txt',
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded', 'Cache-Control: max-age=0', 'Connection: keep-alive'],
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36',
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_HEADER         => 1

    ];

    private const POST_METHOD = 'POST';
    private const GET_METHOD  = 'GET';

    private const DEFAULT_REGEX = [
        
    ];
    

    private const DEFAULT_SPIDER_COMMANDS = [

        ['description' => 'Realizar buscar por CNPJ.', 'action' => 'searchByCNPJ'],
        ['description' => 'Realizar buscar por Inscrição Estadual.', 'action' => ''],

    ];


    public function __construct(
        private string $service     = 'http://www.sintegra.fazenda.pr.gov.br',
        private bool   $debug       =  false,
        private string $captchaPath = './captcha/captcha.jpg'
    ) {
        
    }


    public function run(): void
    {
        $this->options();

        $option = readline("Por favor, escolha uma opção: ");

        if (!key_exists($option, self::DEFAULT_SPIDER_COMMANDS)) {
            echo "Desculpe, a opção selecionada é inválida. :/"; exit;
        }

        $method = self::DEFAULT_SPIDER_COMMANDS[$option]['action'];

        $this->$method();
    }

    public function debug(bool $debugOn = true)
    {
        $this->debug = $debugOn;
    }

    private function options(): void
    {
        echo "\nOlá, pelo o que você está buscando? \n\n";

        foreach (self::DEFAULT_SPIDER_COMMANDS as $key => $command) {
            echo sprintf("[%d]  %s \n", $key, $command['description']);
        }

        echo PHP_EOL;
    }


    private function searchByCNPJ(): void
    {
        $cnpj = (string)readline("Por favor, digite o CNPJ desejado: ");

        $this->captcha();

        $captcha = readline("Por favor, digite o valor do Captcha: ");


        $form    = [
            '_method' => 'POST',
            'data[Sintegra1][CodImage]' => $captcha,
            'data[Sintegra1][Cnpj]' => $cnpj,
            'empresa' => 'Consultar Empresa',
            'data[Sintegra1][Cadicms]' => '',
            'data[Sintegra1][CadicmsProdutor]' => '',
            'data[Sintegra1][CnpjCpfProdutor]' => ''
        ];



        $response = $this->request(self::POST_METHOD, "/sintegra/", $form);
        $company  = $response;

        print_r($company);
        exit;
    }

    private function captcha(): string
    {
        try {
            $image = $this->request(self::GET_METHOD,  sprintf("/sintegra/captcha?".(float)rand()));
    
            $image      = substr($image['response'], $image['headers']['headerSize']);
            
            file_put_contents($this->captchaPath, $image);
    
            if (!file_exists($this->captchaPath)) {
                throw new Exception("Deculpe, Houve um erro ao recuperar a imagem do captcha. :/ ");
            }

            echo sprintf("\nDownload do captcha realizado com sucesso! [ Caminho: %s ] \n", realpath($this->captchaPath));
    
            return realpath($this->captchaPath);

        } catch (Exception $e) {
           
            echo $e->getMessage();
            exit;
        }

    }


    private function parse(string $response)
    {
       $data = [];

       while(true) {
         
       }
    }

    
    private function request(string $method, string $endpoint, array $formData = []): array
    {
        $url  = $this->service . $endpoint;

        $curl = curl_init($url);

        $options = self::DEFAULT_CURL_CONFIG;

        $options[CURLOPT_URL] = $url;
        
        if ($method === self::POST_METHOD) {
            $options[CURLOPT_POST] = 1;
            $options[CURLOPT_POSTFIELDS] = http_build_query($formData);
        }

        curl_setopt_array($curl, $options);
        

        if ($this->debug === true) {
            echo sprintf("\n\n %s ................................... %s \n\n", $method, $url);
        }
        
        $response = curl_exec($curl);
       
        if ($response === false) {
            echo sprintf("Houve um erro ao realizar uma requisição [Rota: %s][Error: %s]", $url, curl_error($curl));
            exit;
        }

        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $httpCode   = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        return ['response' => $response, 'headers' => [
            'headerSize' => $headerSize,
            'httpCode'   => $httpCode
        ]];
    }
}