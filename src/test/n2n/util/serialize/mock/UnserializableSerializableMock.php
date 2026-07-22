<?php

namespace n2n\util\serialize\mock;

final class UnserializableSerializableMock implements \Serializable {
	public string $holeradio;

	public function serialize() {
		// TODO: Implement serialize() method.
	}

	public function unserialize(string $data) {
		// TODO: Implement unserialize() method.
	}

}