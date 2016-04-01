<?php

namespace OpenSkos2\Validator\ConceptScheme;

use OpenSkos2\ConceptScheme;
use OpenSkos2\Validator\AbstractConceptSchemeValidator;
class Creator extends AbstractConceptSchemeValidator
{
  
    protected function validateSchema(ConceptScheme $resource)
    {
       return parent::genericValidate('\CommonProperties\Creator::validate', $resource);
    }
}