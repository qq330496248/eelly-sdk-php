<?php

declare(strict_types=1);

/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eelly\DocsBundle\Adapter;

use Eelly\Exception\RequestException;
use Phalcon\Annotations\Adapter\Memory as MemoryAdapter;
use ReflectionClass;

/**
 * Class ApiDocumentShow.
 */
class ApiDocumentShow extends AbstractDocumentShow implements DocumentShowInterface
{
    /**
     * @var string
     */
    private $class;

    /**
     * @var string
     */
    private $method;

    public function __construct(string $module, string $class, string $method)
    {
        $class = sprintf('Eelly\SDK\%s\Service\%sInterface', ucfirst($module), ucfirst($class));
        $this->class = $class;
        $this->method = $method;
    }

    public function setViewVars(): void
    {
        if (!interface_exists($this->class) || !method_exists($this->class, $this->method)) {
            throw new RequestException(404, null, $this->request, $this->response);
        }
        $interface = new ReflectionClass($this->class);
        $reflectionMethod = $interface->getMethod($this->method);

        $docComment = $this->getDocComment($reflectionMethod->getDocComment());
        $authorsMarkdown = '';
        foreach ($docComment['authors'] as $item) {
            $authorsMarkdown .= sprintf("- %s <%s>\n", $item->getAuthorName(), $item->getEmail());
        }
        $params = [];
        $paramsDocs = [];
        foreach ($docComment['params'] as $item) {
            $varName = $item->getVariableName();
            $paramsDocs[$varName] = $item;
            $typeValue = $type = $item->getType();
            if ($type instanceof \phpDocumentor\Reflection\Types\Compound) {
                $typeValue = [];
                foreach ($type->getIterator() as $typeItem) {
                    $typeValue[] = $typeItem;
                }
                $typeValue = implode(' or ', $typeValue);
            }
            $params[$varName] = [
                'name'         => $varName,
                'type'         => $typeValue,
                'allowsNull'   => '否',
                'defaultValue' => ' ',
                'description'  => $item->getDescription(),
            ];
        }
        if (0 == $reflectionMethod->getNumberOfParameters()) {
            $paramsMarkdown = '';
        } else {
            $paramsMarkdown = <<<EOF
### 请求参数
参数名|类型|是否可选|默认值|说明
-----|----|-----|-------|---\n
EOF;
        }
        foreach ($reflectionMethod->getParameters() as $value) {
            $name = $value->getName();
            $params[$name] = [
                'name'         => $name,
                'type'         => $value->getType(),
                'allowsNull'   => '否',
                'defaultValue' => '无',
                'description'  => $paramsDocs[$name]->getDescription(),
            ];
            if ($value->isDefaultValueAvailable()) {
                $params[$name]['defaultValue'] = $value->getDefaultValue();
                $params[$name]['allowsNull'] = '是';
                $params[$name]['defaultValue'] = preg_replace("/\s/", '', var_export($params[$name]['defaultValue'], true));
            }
        }
        foreach ($params as $value) {
            $paramsMarkdown .= sprintf("%s|%s|%s|%s|%s\n",
                $value['name'],
                $value['type'],
                $value['allowsNull'],
                $value['defaultValue'],
                $value['description']);
        }
        $methodMarkdown = $this->getFileContent($interface->getFileName(), $reflectionMethod->getStartLine(), 1);
        $methodMarkdown = trim($methodMarkdown);
        $annotations = new MemoryAdapter();
        $collection = $annotations->getMethod(
            $reflectionMethod->class,
            $reflectionMethod->name
        );

        $requestExample = '';
        if ($collection->has('requestExample')) {
            $arguments = $collection->get('requestExample')->getArgument(0);
            if (is_array($arguments)) {
                $requestExample = <<<EOF
### 请求示例
```json\n
EOF;
                $requestExample .= json_encode($arguments, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $requestExample .= "\n```";
            }
        }
        $returnExample = '';
        if ($collection->has('returnExample')) {
            $arguments = $collection->get('returnExample')->getArgument(0);
            if ($arguments) {
                $returnExample .= "### 返回示例\n```\n";
                $returnExample .= json_encode(
                        ['data' => $arguments],
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                    )."\n```";
            }
        }
        $markdown = <<<EOF
# {$docComment['summary']}
```php
$methodMarkdown
```
$paramsMarkdown

$requestExample
        
{$docComment['description']}

$returnExample

### 代码贡献
$authorsMarkdown
EOF;
        $this->view->markup = $this->parserMarkdown($markdown);
    }
}
