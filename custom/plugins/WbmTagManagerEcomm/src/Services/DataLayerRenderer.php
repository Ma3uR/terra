<?php declare(strict_types=1);

namespace Wbm\TagManagerEcomm\Services;

use Doctrine\DBAL\Connection;
use Twig\Environment;

class DataLayerRenderer implements DataLayerRendererInterface
{
    const STRING_VALUES = [
        'name',
        'id',
        'brand',
        'category',
    ];

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var array
     */
    private $variables = [];

    /**
     * @var array
     */
    private $dataLayer = [];

    public function __construct(
        Environment $twig,
        Connection $connection
    ) {
        $this->twig = clone $twig;
        $this->connection = $connection;
    }

    public function renderDataLayer(string $route): DataLayerRendererInterface
    {
        $properties = $this->getChildrenList(null, $route);

        $dataLayer = $this->fillValues($properties);

        try {
            $template = $this->twig->createTemplate($dataLayer);

            $dataLayer = $template->render($this->getVariables($route));
        } catch (\Exception $e) {
            $dataLayer = json_encode(['error' => $e->getMessage()]);
        }

        $dataLayer = json_decode($dataLayer, true);

        if (!empty($dataLayer)) {
            array_walk_recursive($dataLayer, [$this, 'castArrayValues']);
        }

        $this->dataLayer[$route] = json_encode($dataLayer);

        return $this;
    }

    public function getVariables(string $route): array
    {
        return $this->variables[$route];
    }

    public function setVariables(string $route, $variables): DataLayerRendererInterface
    {
        $this->variables[$route] = $variables;

        return $this;
    }

    public function getDataLayer($route): ?string
    {
        return @$this->dataLayer[$route];
    }

    public function fillValues(array $dataLayer): string
    {
        $dataLayer = json_encode($dataLayer);

        $search = [',{"endArrayOf":true}'];
        $replace = ['{% endverbatim %}{% if not loop.last %},{% endif %}{% endfor %}{% verbatim %}'];

        $dataLayer = str_replace($search, $replace, $dataLayer);

        while (preg_match('/({"startArrayOf":".*?"},)/i', $dataLayer, $matches)) {
            foreach ($matches as $match) {
                $foreachObj = json_decode(rtrim($match, ','));
                if ($foreachObj->startArrayOf) {
                    $arguments = explode(' in ', $foreachObj->startArrayOf);
                    $dataLayer = str_replace(
                        $match,
                        '{% endverbatim %}{% for ' . @$arguments[0] . ' in ' . @$arguments[1] . ' %}{% verbatim %}',
                        $dataLayer
                    );
                }
            }
        }

        $dataLayer = '{% verbatim %}' . $dataLayer . '{% endverbatim %}';

        return $dataLayer;
    }

    public function getChildrenList($id = null, $module = null): array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->from('wbm_data_layer_properties', 'property');

        if ($id !== null) {
            $qb->where('property.parent_id = :parentId')
                ->setParameter(':parentId', $id);
        } else {
            $qb->where('property.parent_id IS NULL');
        }

        $qb->andWhere('property.module = :moduleName')
            ->setParameter(':moduleName', $module);

        $qb->select(
            [
                'property.name',
                'property.value',
                'property.id',
                'property.child_count',
            ]
        );
        $properties = $qb->execute()->fetchAll(\PDO::FETCH_ASSOC);

        $namedProperties = [];

        foreach ($properties as $key => &$property) {
            $subProperties = null;
            if ((int) $property['child_count'] > 0) {
                $subProperties = $this->getChildrenList($property['id'], $module);
            }
            $value = $property['value'];
            $name = $property['name'];
            unset(
                $property['name'],
                $property['value'],
                $property['id'],
                $property['child_count']
            );

            if (!empty($subProperties)) {
                if (empty($value)) {
                    $property = $subProperties;
                } else {
                    $property = [
                        ['startArrayOf' => $value],
                        $subProperties,
                        ['endArrayOf' => true],
                    ];
                }
            } else {
                $property = '{% endverbatim %}' . $value . '{% verbatim %}';
            }

            $namedProperties[$name] = $property;
        }

        return $namedProperties;
    }

    private function castArrayValues(&$value, $key): void
    {
        if (in_array($key, self::STRING_VALUES)) {
            return;
        }

        if (preg_match('/^\"(.*)\"$/', $value)) {
            $value = json_decode($value);

            return;
        }

        switch (true) {
            case is_array(json_decode($value)):
            case is_int(json_decode($value)):
            case is_float(json_decode($value)):
            case is_bool(json_decode($value)):
                $value = json_decode($value);
        }
    }
}
