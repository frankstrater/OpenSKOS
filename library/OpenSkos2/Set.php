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

namespace OpenSkos2;

use OpenSkos2\Rdf\Resource;
use OpenSkos2\Namespaces\Dcmi;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\PersonManager;
use Rhumsaa\Uuid\Uuid;

class Set extends Resource
{

    const TYPE = Dcmi::DATASET;

    public function __construct($uri = null)
    {
        parent::__construct($uri);
        $this->addProperty(Rdf::TYPE, new Uri(self::TYPE));
    }

    public function getTenantUri()
    {
        $tenants = $this->getProperty(DcTerms::PUBLISHER);
        if (count($tenants) < 1) {
            return null;
        } else {
            return $tenants[0];
        }
    }

  /**
     * Ensures the concept has metadata for tenant, set, creator, date submited, modified and other like this.
     * @param \OpenSkos2\Tenant $tenant
     * @param \OpenSkos2\Set $set
     * @param \OpenSkos2\Person $person
     * @param \OpenSkos2\PersonManager $personManager
     * @param \OpenSkos2\LabelManager | null  $labelManager
     * @param  \OpenSkos2\Rdf\Resource | null $existingResource, optional $existingResource of one of concrete child types used for update
     * override for a concerete resources when necessary
     */
     public function ensureMetadata(
        \OpenSkos2\Tenant $tenant, 
        \OpenSkos2\Set $set, 
        \OpenSkos2\Person $person,
        PersonManager $personManager, 
        $labelManager = null, 
        $existingConcept = null, 
        $forceCreationOfXl = false)
    {
         $nowLiteral = function () {
            return new Literal(date('c'), null, Literal::TYPE_DATETIME);
        };

        $forFirstTimeInOpenSkos = [
            OpenSkos::UUID => new Literal(Uuid::uuid4()),
            DcTerms::PUBLISHER => new Uri($tenant->getUri()),
            OpenSkos::TENANT => $tenant->getCode(),
            DcTerms::DATESUBMITTED => $nowLiteral()
        ];

        foreach ($forFirstTimeInOpenSkos as $property => $defaultValue) {
            if (!$this->hasProperty($property)) {
                $this->setProperty($property, $defaultValue);
            }
        }

        $this->resolveCreator($person, $personManager);

        $this->setModified($person);
    }

    // TODO: discuss the rules for generating Uri's for non-concepts
    protected function assembleUri($tenant, $set, $uuid, $notation, $init)
    {
        return $tenant->getUri() . "" . $uuid;
    }
}
