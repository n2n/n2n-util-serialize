<?php

namespace n2n\util\serialize\mock;

use n2n\util\col\TypedArray;
use n2n\util\col\attribute\ValueType;
use DateTime;

#[ValueType(DateTime::class)]
final class DateTimeTypedArrayMock extends TypedArray {

}