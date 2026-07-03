<?php

declare(strict_types=1);

namespace App\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

class UploadedFile implements UploadedFileInterface
{
    private bool $moved = false;

    public function __construct(
        private StreamInterface $stream,
        private ?int $size = null,
        private int $error = UPLOAD_ERR_OK,
        private ?string $clientFilename = null,
        private ?string $clientMediaType = null
    ) {}

    public function getStream(): StreamInterface
    {
        if ($this->moved) {
            throw new RuntimeException('File has already been moved');
        }

        return $this->stream;
    }

    public function moveTo(string $targetPath): void
    {
        if ($targetPath === '') {
            throw new InvalidArgumentException('Target path cannot be empty');
        }

        if ($this->moved) {
            throw new RuntimeException('File has already been moved');
        }

        $result = file_put_contents($targetPath, (string) $this->stream);
        if ($result === false) {
            throw new RuntimeException("Cannot move file to $targetPath");
        }

        $this->stream->close();
        $this->moved = true;
    }

    public function getSize(): ?int
    {
        return $this->size ?? $this->stream->getSize();
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }
}
