<?php declare(strict_types=1);

namespace Webmp\GoogleReviewFeed\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmp\GoogleReviewFeed\Helper\GoogleReviewFeedHelper;

/**
 * Class GenerateGoogleReviewFeedCommand
 * @package Webmp\GoogleReviewFeed\Command
 */
class GenerateGoogleReviewFeedCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'webmasterei:generate_google_review_feed';

    /**
     * @var GoogleReviewFeedHelper
     */
    private $googleReviewFeedHelper;

    /**
     * GenerateGoogleReviewFeedCommand constructor.
     * @param GoogleReviewFeedHelper $googleReviewFeedHelper
     * @param null $name
     */
    public function __construct(GoogleReviewFeedHelper $googleReviewFeedHelper, $name = null)
    {
        $this->googleReviewFeedHelper = $googleReviewFeedHelper;

        parent::__construct($name);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->googleReviewFeedHelper->generateFeedFile();
    }
}
