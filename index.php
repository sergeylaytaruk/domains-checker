<?php
$strDomains = $argv[1];
$parsed = preg_replace("#(\t|\r|\n|\s)#ium", " ", $strDomains);
$parsed = preg_replace("#\s{2,}#u", " ", $parsed);
$domains = explode(" ", trim($parsed));
$domainsChecker = new DomainsChecker($domains);
$domainsChecker->run();

class DomainsChecker
{
    private $domainsStatistic;
    private $domainsChecked;

    public function __construct($domains)
    {
        $this->domainsChecked = [];
        $this->domainsStatistic = [];
        foreach ($domains as $inx => $domain) {
            $this->domainsStatistic[$inx] = [
                'domain' => str_ireplace("vm_", "", $domain),
                'status' => false,
            ];
        }
    }

    public function run()
    {
        $this->domainsChecked = [];
        $condition = true;
        while ($condition) {
            foreach ($this->domainsStatistic as $inx => $item) {
                if (!in_array($item['domain'], $this->domainsChecked)) {
                    $result = $this->sendRequest($item['domain']);
                    if ($result) {
                        $this->domainsChecked[] = $item['domain'];
                        $this->domainsStatistic[$inx]['status'] = true;
                    }
                }
            }
            $this->printStatistic();
            if (count($this->domainsStatistic) > count($this->domainsChecked)) {
                sleep(60);
            } else {
                $condition = false;
                echo "\n END \n";
            }
        }
    }

    private function sendRequest($domain)
    {
        $url = "https:///" . $domain . ".vetmanager2.ru/login.php";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: text/html'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if (empty($err) && (mb_strlen($response) > 2000 || false !== mb_stripos($response, "Запрещен доступ по IP"))) {
            return true;
        } else {
            return false;
        }
    }

    private function printStatistic()
    {
        system('clear');
        echo str_pad("", 29, "_",STR_PAD_RIGHT) . "\n";
        foreach ($this->domainsStatistic as $inx => $item) {
            $leftColumn = str_pad("| " . $item['domain'], 20, " ",STR_PAD_RIGHT);
            $rightColumn = str_pad(($item['status']? "done" : "wait"), 6, " ",STR_PAD_LEFT);
            echo $leftColumn . "|" . $rightColumn . " | \n";
        }
        echo str_pad("", 29, "-",STR_PAD_RIGHT);
    }
}
