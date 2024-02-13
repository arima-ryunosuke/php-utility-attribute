<?php

use ryunosuke\utility\attribute\Attribute\Friend;
use ryunosuke\utility\attribute\ClassTrait\FriendTrait;

// nodoccomment
class NoTarget
{
    use FriendTrait;
}

class NoAnnotation
{
    use FriendTrait;

    #[Friend]
    private $private;
}

/**
 * merged
 */
class AlreadyAnnotation
{
    use FriendTrait;

    #[Friend]
    private $private;
}

if (true) {
    class IndentNoAnnotation
    {
        use FriendTrait;

        #[Friend]
        private $private;
    }

    /**
     * merged
     */
    class IndentAlreadyAnnotation
    {
        use FriendTrait;

        #[Friend]
        private $private;
    }
}
