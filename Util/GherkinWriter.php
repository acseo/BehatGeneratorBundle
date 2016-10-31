<?php

namespace ACSEO\Bundle\BehatGeneratorBundle\Util;

/**
 * Write text for output in Gherkin syntax.
 */
class GherkinWriter
{
    protected $indent;
    protected $output;

    public function __construct()
    {
        $this->init();
    }

    /**
     * Add new line in the output.
     *
     * @param string $text
     * @param integer $indent is the indentation level to apply (optional).
     * @return GerkinWriter
     */
    public function addLine($text, $indent = null)
    {
        if ($indent !== null) {
            $this->indent = $indent;
        }

        $this->output[] = $this->generateSpaces() . $text;

        return $this;
    }

    /**
     * Add a line with one more indentation level.
     *
     * @param string $text
     * @return GerkinWriter
     */
    public function subLine($text)
    {
        $this->indent++;
        $this->addLine($text);

        return $this;
    }

    /**
     * Add an empty line.
     *
     * @return GerkinWriter
     */
    public function emptyLine()
    {
        $indent = $this->indent;
        $this->addLine('', 0);
        $this->indent = $indent;

        return $this;
    }

    /**
     * Add json in output.
     *
     * @param array $json
     * @param integer $indent is the indentation level to apply (optional).
     * @return GerkinWriter
     */
    public function addJson(array $json, $indent = null)
    {
        if ($indent !== null) {
            $this->indent = $indent;
        }

        $space = $this->generateSpaces();

        $json = json_encode($json, JSON_PRETTY_PRINT);
        $json = str_replace('    ', $space.'    ', $json);
        $json = substr($json, 0, -1) . $space . '}';

        $this
            ->addLine('"""')
            ->addLine($json)
            ->addLine('"""')
        ;

        return $this;
    }

    /**
     * Add a comment in the output.
     *
     * @param string   $text
     * @param int|null $indent
     */
    public function addComment($text, $indent = null)
    {
        return $this->addLine('# ' . $text, $indent);
    }

    /**
     * Generate text output based on all lines.
     *
     * @return string
     */
    public function generateOutput()
    {
        $output = implode(PHP_EOL, $this->output);
        $output .= PHP_EOL; // Add an empty line at the end of the file

        $this->init();

        return $output;
    }

    /**
     * Initialize wtriter attributes.
     */
    protected function init()
    {
        $this->output = array();
        $this->indent = 0;
    }

    /**
     * Generate spaces depending on writer indent level.
     *
     * @return string
     */
    protected function generateSpaces()
    {
        $spaces = '';
        for ($i = 0; $i < $this->indent; $i++) {
            $spaces .= '    ';
        }

        return $spaces;
    }
}
