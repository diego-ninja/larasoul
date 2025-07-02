<?php

namespace Ninja\Larasoul\DTO;

use Bag\Attributes\MapInputName;
use Bag\Attributes\MapOutputName;
use Bag\Bag;
use Bag\Mappers\SnakeCase;
use Ninja\Larasoul\Enums\IDBarcodeStatus;
use Ninja\Larasoul\Enums\IDDigitalSpoof;
use Ninja\Larasoul\Enums\IDFaceStatus;
use Ninja\Larasoul\Enums\IDStatus;
use Ninja\Larasoul\Enums\IDTextStatus;
use Ninja\Larasoul\Enums\IDValidity;

#[MapInputName(SnakeCase::class)]
#[MapOutputName(SnakeCase::class)]
final readonly class DocumentSignals extends Bag
{
    public function __construct(
        public int $idAge,
        public float $idFaceMatchScore,
        public IDBarcodeStatus $idBarcodeStatus,
        public IDFaceStatus $idFaceStatus,
        public IDTextStatus $idTextStatus,
        public IDDigitalSpoof $isIdDigitalSpoof,
        public IDStatus $isFullIdCaptured,
        public IDValidity $idValidity,
    ) {}
}
