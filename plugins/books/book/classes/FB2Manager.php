<?php

namespace Books\Book\Classes;

use Books\Book\Classes\Exceptions\FBParserException;
use System\Models\File;
use Tizis\FB2\FB2Controller;
use Tizis\FB2\Model\Book as TizisBook;

class FB2Manager
{
    protected FB2Controller $parser;

    public function __construct(protected File $fb2)
    {
    }

    public function apply(): TizisBook
    {
        $file = file_get_contents($this->fb2->getLocalPath());

        try {
            $this->parser = new FB2Controller($file);
            $this->parser->withNotes();
            $this->parser->startParse();
        } catch (\Exception $exception) {
            throw new FBParserException();
        }

        return $this->parser->getBook();
    }
}
