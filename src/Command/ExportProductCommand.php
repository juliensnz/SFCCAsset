<?php

declare(strict_types=1);

namespace App\Command;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ExportProductCommand extends Command
{
    protected static $defaultName = 'app:export:product';

    private const BATCH_SIZE = 100;

    /** @var SymfonyStyle */
    private $io;

    /** @var AkeneoPimEnterpriseClientInterface */
    private $apiClient;

    public function __construct(
        AkeneoPimEnterpriseClientInterface $apiClient
    ) {
        parent::__construct(static::$defaultName);

        $this->apiClient = $apiClient;
    }

    protected function configure()
    {
        $this
            ->setDescription('Export all products in an xml file compatible with sfcc')
            ->addArgument('filePath', InputArgument::REQUIRED, 'The filePath of the file to generate.')
            ->addArgument('assetAttribute', InputArgument::REQUIRED, 'The asset attribute code to put images in')
            ->addOption('apiUsername', null, InputOption::VALUE_OPTIONAL, 'The username of the user.', getenv('AKENEO_API_USERNAME'))
            ->addOption('apiPassword', null, InputOption::VALUE_OPTIONAL, 'The password of the user.', getenv('AKENEO_API_PASSWORD'))
            ->addOption('apiClientId', null, InputOption::VALUE_OPTIONAL, '', getenv('AKENEO_API_CLIENT_ID'))
            ->addOption('apiClientSecret', null, InputOption::VALUE_OPTIONAL, '', getenv('AKENEO_API_CLIENT_SECRET'))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);

        $filePath = $input->getArgument('filePath');
        $assets = json_decode(file_get_contents($filePath), true);
        $this->io->success('OK');

        $assetAttribute = $input->getArgument('assetAttribute');

        $productsPage = $this->apiClient->getProductApi()->listPerPage(self::BATCH_SIZE, true, ['attributes' => $assetAttribute, 'pagination_type' => 'search_after']);
        $this->io->progressStart();

        $exportedProducts = '';

        while ($productsPage->hasNextPage()) {
            $products = $productsPage->getItems();

            foreach ($products as $product) {
                if (!empty($product['values'])) {
                    $assetCodes = $product['values'][$assetAttribute][0]['data'];

                    $images = array_reduce($assetCodes, function (string $result, string $assetCode) use ($assets) {
                        if (!isset($assets[$assetCode])) {
                            return $result;
                        }

                        return $result . sprintf(
                            <<<XML

                                <image path="%s"/>
                            XML,
                            $assets[$assetCode]['image']
                        );
                    }, '');

                    if ($images !== '') {
                        $exportedProducts .= sprintf(
                            <<<XML

                            <product product-id="%s">
                                <images>
                                    <image-group view-type="large">
                                        %s
                                    </image-group>
                                </images>
                            </product>
                            XML,
                            $product['identifier'],
                            $images
                        );
                    }
                }
            }
            $this->io->progressAdvance(count($products));

            $productsPage = $productsPage->getNextPage();
        }

        $exportedFile = sprintf(
            <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <catalog xmlns="http://www.demandware.com/xml/impex/catalog/2006-10-31" catalog-id="%s">
            <header>
                <image-settings>
                <internal-location base-path="%s"></internal-location>
                <view-types>
                    <view-type>large</view-type>
                </view-types>
                </image-settings>
            </header>
            %s
            </catalog>
            XML,
            'catalogId',
            'basePath',
            $exportedProducts
        );

        file_put_contents(sprintf('export_product_%s.xml', time()), sprintf($exportedFile, 'YourCatalog', 'imagesInternalLocation', 'categoryId'));

        // var_dump($products);die;

        // $this->io->progressStart();
        // $indexedAssets = [];
        // $cpt = 0;
        // foreach ($products as $product) {
        //     if(!empty($product['values'])) var_dump($product['id']);
        //     $cpt++;

        //     if ($cpt % self::BATCH_SIZE === 0) {
        //         $this->io->progressAdvance(self::BATCH_SIZE);
        //     }
        // }

        $this->io->progressFinish();
    }
}
