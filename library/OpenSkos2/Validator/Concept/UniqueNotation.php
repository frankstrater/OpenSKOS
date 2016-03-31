<?php

/**
 * OpenSKOS
 *
 * LICENSE
 *
 * This source file is subject to the GPLv3 license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @category   OpenSKOS
 * @package    OpenSKOS
 * @copyright  Copyright (c) 2015 Picturae (http://www.picturae.com)
 * @author     Picturae
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

namespace OpenSkos2\Validator\Concept;

use OpenSkos2\Concept;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Validator\AbstractConceptValidator;
use OpenSkos2\Validator\DependencyAware\ResourceManagerAware;
use OpenSkos2\Validator\DependencyAware\ResourceManagerAwareTrait;
use OpenSkos2\Validator\DependencyAware\TenantAware;
use OpenSkos2\Validator\DependencyAware\TenantAwareTrait;

class UniqueNotation extends AbstractConceptValidator implements ResourceManagerAware, TenantAware
{
    use ResourceManagerAwareTrait;
    use TenantAwareTrait;

    /**
     * @param Concept $concept
     * @return bool
     */
    protected function validateConcept(Concept $concept)
    {
        if ($concept->isPropertyEmpty(Skos::NOTATION)) {
            return true;
        }

        $params = [];
        $params[] = [
            'operator' => \OpenSkos2\Sparql\Operator::EQUAL,
            'predicate' => Skos::NOTATION,
            'value' => $concept->getProperty(Skos::NOTATION)
        ];

        if (!$concept->isPropertyEmpty(Skos::INSCHEME)) {
            $params[] = [
                'operator' => \OpenSkos2\Sparql\Operator::EQUAL,
                'predicate' => Skos::INSCHEME,
                'value' => $concept->getProperty(Skos::INSCHEME)
            ];
        }

        $hasOther = $this->getResourceManager()->askForMatch(
            $params,
            $concept->getUri()
        );

        if (!$hasOther) {
            return true;
        } else {
            $this -> errorMessages[] = "The notation in the submitted concept has been alredy used in the given schema: use another notation.";
        }

        return false;
    }

}
