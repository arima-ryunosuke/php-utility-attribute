<?php

use ryunosuke\utility\attribute\Attribute\Friend;
use ryunosuke\utility\attribute\ClassTrait\FriendTrait;

/**
 * @auto-document-Friend:begin
 * @property $privateField
 * @auto-document-Friend:end
 */
return new class(1) {
    use FriendTrait;

    public function __construct(
        #[Friend]
        private $privateField
    ) {
    }
};
