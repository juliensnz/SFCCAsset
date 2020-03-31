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

class ExportAssetCommand extends Command
{
    protected static $defaultName = 'app:export:asset';

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
            ->setDescription('Export all assets in an indexed json file')
            ->addArgument('filePath', InputArgument::REQUIRED, 'The filePath of the file to generate.')
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
        $this->io->success('OK');

        $assetFamilies = $this->apiClient->getAssetFamilyApi()->all();

        $this->io->progressStart();
        $indexedAssets = [];
        $cpt = 0;
        foreach ($assetFamilies as $assetFamily) {
            $assets = $this->apiClient->getAssetManagerApi()->all($assetFamily['code']);

            foreach ($assets as $asset) {
                if (isset($asset['values'][$assetFamily['attribute_as_main_media']])) {
                    $indexedAssets[$asset['code']] = [
                        'code' => $asset['code'],
                        'image' => $asset['values'][$assetFamily['attribute_as_main_media']][0]['data']
                    ];
                    $cpt++;
                }

                if ($cpt % self::BATCH_SIZE === 0) {
                    $this->io->progressAdvance(self::BATCH_SIZE);
                }
            }
        }

        file_put_contents($filePath, json_encode($indexedAssets));

        $this->io->progressFinish();
    }
}
