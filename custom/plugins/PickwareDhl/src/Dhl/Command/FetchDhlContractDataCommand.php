<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\Dhl\Command;

use Pickware\PickwareDhl\Dhl\DhlBcpConfigScraper\DhlBcpConfigScraper;
use Pickware\PickwareDhl\Dhl\DhlBcpConfigScraper\DhlContractData;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A CLI command that verifies a DHL account and returns the DHL contract data for this BCP account.
 */
class FetchDhlContractDataCommand extends Command
{
    /**
     * Setup the command name, arguments and help text.
     */
    public function configure(): void
    {
        $this
            ->setName('pickware-dhl:contract-data:fetch')
            ->setDescription('Fetch contract data from the DHL BCP.')
            ->addArgument('username', InputArgument::REQUIRED, 'DHL Business Customer Portal user name')
            ->addArgument('password', InputArgument::REQUIRED, 'DHL Business Customer Portal password')
            ->setHelp(
                '<info>%command.name%</info> uses the supplied credentials to log into the DHL Business Customer ' .
                'Portal and extract the customer number and the billing numbers from the portal.'
            );
    }

    /**
     * Run the DHL scraping.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $input->getArgument('username');
        $password = $input->getArgument('password');
        $dhlService = new DhlBcpConfigScraper($user, $password);
        $output->writeln('<info>Logging into the DHL Business Customer Portal and fetching contract data ...</info>');

        /** @var DhlContractData $dhlContactData */
        $dhlContractData = $dhlService->fetchContractData();
        $output->writeln('<info>Success!</info>');
        $output->writeln(sprintf('<info>Customer number / EKP:</info> %s', $dhlContractData->getCustomerNumber()));
        $contractPositions = $dhlContractData->getBookedProducts();
        if (empty($contractPositions)) {
            $output->writeln('<error>No billing numbers found.</error>');
        } else {
            $output->writeln('<info>Billing numbers:</info>');
            foreach ($dhlContractData->getBookedProducts() as $dhlContractPosition) {
                $output->writeln(sprintf('  %s', $dhlContractPosition->getProduct()->getName()));
                $output->writeln(sprintf('    Code: %s', $dhlContractPosition->getProduct()->getCode()));
                $output->writeln(sprintf('    Procedure: %s', $dhlContractPosition->getProduct()->getProcedure()));
                $output->writeln(sprintf('    Billing number(s): %s', implode(', ', $dhlContractPosition->getBillingNumbers())));
                $output->writeln(sprintf('    Participation(s): %s', implode(', ', $dhlContractPosition->getParticipations())));
            }
        }

        return 0;
    }
}
