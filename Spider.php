<?php
declare(strict_types=1);

class Spider {
    
    private string $service = 'http://www.sintegra.fazenda.pr.gov.br';
  

    private const POST_METHOD = 'POST';
    private const GET_METHOD  = 'GET';

    private const  DEFAULT_CURL_CONFIG = [
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_COOKIEFILE     => './cookies/cookies.txt',
        CURLOPT_COOKIEJAR      => './cookies/cookies.txt',
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded', 'Cache-Control: max-age=0', 'Connection: keep-alive'],
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36',
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_HEADER         => 1
    ];

    private const DEFAULT_REGEX = [
        'btn_inscription'  => '/<button\s+type="submit"\s+id="consultar"\s+name="consultar"\s+label="">\s*(.*?)\s*<\/button>/',
        'next_inscription' => '/value="(.*?)"/',
        'label'            => '/<td\s+class="form_label"[^>]*>(.*?)<\/td>/s',
        'value'            => '/<td\s+class="form_conteudo"[^>]*>(.*?)<\/td>/s',
        'error_message'    => '/<td\s+class="erro_msg_custom">(.*?)<\/td>/'
    ];

    public function __construct(
        private bool   $debug       = false,
        private string $captchaPath = './captcha/captcha.jpg'
    ) {}
    
    public function run(): void
    {
        echo "\n\nOlá! \n\n";
        $this->searchByCNPJOrIe();
    }

    public function debug(bool $debugOn = true): void
    {
        $this->debug = $debugOn;
    }

    private function searchByCNPJOrIe(): void
    {
        $document = trim(readline("Por favor, digite o CNPJ ou Inscrição Estadual da empresa que deseja buscar: "));
        
        if (strlen(str_replace('-', '', $document)) === 10) {
            $ie   = $document;
        } else {
            $cnpj = $document;
        }

        $this->captcha();
        
        $captcha = readline("Por favor, digite o valor do Captcha: ");
        $form = [
            '_method' => 'POST',
            'data[Sintegra1][CodImage]' => $captcha,
            'data[Sintegra1][Cnpj]' => $cnpj ?? '',
            'empresa' => 'Consultar Empresa',
            'data[Sintegra1][Cadicms]' => $ie ?? '',
            'data[Sintegra1][CadicmsProdutor]' => '',
            'data[Sintegra1][CnpjCpfProdutor]' => ''
        ];
       
        $response = $this->request(self::POST_METHOD, "/sintegra/", $form)['response'];
        $this->error($response);
        $data = $this->anothersInscriptions($response);

        $company = $this->parseInfo($data);
        
        print_r($company);
        exit;
    }

    private function captcha(): string
    {
        try {
            $image = $this->request(self::GET_METHOD, sprintf("/sintegra/captcha?%s", (float)rand()))['response'];
            file_put_contents($this->captchaPath, substr($image, $this->getImageHeaderSize($image)));
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

    private function anothersInscriptions(string $response): array
    {
        $inscriptions = [];
        while(true) {
            $inscriptions[] = html_entity_decode($response);
            preg_match(self::DEFAULT_REGEX['btn_inscription'], $response, $btnNext);
            if (empty($btnNext)) {  
                break;
            }
            preg_match_all(self::DEFAULT_REGEX['next_inscription'], $response, $matches);
            $response = $this->nextPage($matches[1][1]);
        }
        return $inscriptions;
    }

    private function nextPage(string $previewsValue): string
    {
        $value = str_replace(['value="', '"'], "", $previewsValue);
        $form = [
            '_method' => 'POST',
            'data[Sintegra1][campoAnterior]: ' => $value,
            'consultar' => '',
        ];
        $request = $this->request(self::POST_METHOD, '/sintegra/sintegra1/consultar', $form);
        return $request['response'];
    }


    private function parseInfo(array $data): array
    {
        $companies = [];
        foreach ($data as $page) {
            $labels = $values = [];
            if (preg_match_all(self::DEFAULT_REGEX['label'], $page, $labels)) {
                $labels = $this->labels($labels[1]);
            }
            if (preg_match_all(self::DEFAULT_REGEX['value'], $page, $values)) {
                array_pop($values[1]);
                $values = $values[1];
            }
            if (!empty($labels) && !empty($values)) {
                foreach($labels as $index => $key) {
                    if (key_exists($index, $values)) {
                        $value = trim($values[$index]);
                        if ($key === 'atividade_principal') {
                            $value = $this->economicActivity($value);
                        }
                        if($key === 'atividades_secundarias') {
                            $value = $this->economicActivities($value);
                        }
                        $result[$key] = $value;
                    }
                }
                $companies[] = $result;
            }
        }
       
        return $companies;
    }

    private function economicActivities(string $value): array
    {
        $activities = explode('<br />', $value);

        foreach ($activities as $key => $activity) {
            if(!empty($activity)) {
                $activities[] = $this->economicActivity($activity);
            }
            unset($activities[$key]);
        }

        return $activities;
    }

    private function economicActivity(string $principalActivity): array
    {
        $explode = explode('-', $principalActivity);
        [$code, $description] = $explode;
        return ['codigo' => $code, 'descricao' => trim($description)];
    }

    private function labels(array $labels): array
    {
        array_pop($labels);
        return array_map(function(string $label) {
            $label = str_replace(':', '', $label);
            $label = strtolower($label);
            if ($label == 'inscrição estadual') {
                return 'ie';
            }
            if ($label === 'atividade econômica principal') {
                return "atividade_principal";
            }
            if ($label === 'atividade(s) econômica(s) secundária(s)') {
                return "atividades_secundarias";
            }
            if ($label === 'nome empresarial') {
                return "razao_social";
            }
            if ($label === 'início das atividades') {
                return "data_inicio";
            }
            $label = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label);
            $label = preg_replace("/[^a-zA-Z0-9 ]+/", "", $label); // remove o ' que é inserido pelo icov 
            return str_replace(' ', '_', $label);
        }, $labels);
    }

    private function error(string $response): void
    {
        if (preg_match(self::DEFAULT_REGEX['error_message'], $response, $matches)) {
            $message = mb_convert_encoding($matches[1], 'UTF-8', 'ISO-8859-1');
            echo sprintf("\n\n\033[0;31mDesculpe, houve um erro ao consultar o CNPJ [ERROR: %s]\033[0m \n\n", strtolower($message));
            exit;
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
        curl_close($curl);
        return ['response' => $response, 'headers' => ['headerSize' => $headerSize]];
    }

    private function getImageHeaderSize(string $image): int
    {
        return strpos($image, "\r\n\r\n") + 4;
    }
}
