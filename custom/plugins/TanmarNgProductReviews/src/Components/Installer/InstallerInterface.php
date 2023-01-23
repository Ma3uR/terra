<?php

namespace Tanmar\ProductReviews\Components\Installer;

interface InstallerInterface {

    const GER_ISO = 'de-DE';
    const EN_ISO = 'en-GB';

    public function install();

    public function uninstall();
}
