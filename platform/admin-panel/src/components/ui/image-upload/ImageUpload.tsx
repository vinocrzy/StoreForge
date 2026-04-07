import { useState, useRef, type DragEvent, type ChangeEvent } from 'react';
import Button from '../button/Button';

export interface ImageFile {
  id: string;
  file?: File;
  url: string;
  is_primary: boolean;
  preview: string;
}

interface ImageUploadProps {
  images: ImageFile[];
  onImagesChange: (images: ImageFile[]) => void;
  maxImages?: number;
  maxSizeMB?: number;
}

const ImageUpload: React.FC<ImageUploadProps> = ({
  images,
  onImagesChange,
  maxImages = 10,
  maxSizeMB = 5,
}) => {
  const [isDragging, setIsDragging] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const handleDragEnter = (e: DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(true);
  };

  const handleDragLeave = (e: DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(false);
  };

  const handleDragOver = (e: DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    e.stopPropagation();
  };

  const validateFile = (file: File): boolean => {
    // Check file type
    if (!file.type.startsWith('image/')) {
      setError('Only image files are allowed');
      return false;
    }

    // Check file size
    const sizeMB = file.size / (1024 * 1024);
    if (sizeMB > maxSizeMB) {
      setError(`Image size must be less than ${maxSizeMB}MB`);
      return false;
    }

    return true;
  };

  const processFiles = (files: FileList | File[]) => {
    setError(null);
    const fileArray = Array.from(files);

    // Check max images limit
    if (images.length + fileArray.length > maxImages) {
      setError(`Maximum ${maxImages} images allowed`);
      return;
    }

    const validFiles: ImageFile[] = [];

    fileArray.forEach((file) => {
      if (validateFile(file)) {
        const reader = new FileReader();
        reader.onload = (e) => {
          const newImage: ImageFile = {
            id: `temp-${Date.now()}-${Math.random()}`,
            file,
            url: '',
            is_primary: images.length === 0 && validFiles.length === 0,
            preview: e.target?.result as string,
          };
          validFiles.push(newImage);

          // Update images when all files are processed
          if (validFiles.length === fileArray.length) {
            onImagesChange([...images, ...validFiles]);
          }
        };
        reader.readAsDataURL(file);
      }
    });
  };

  const handleDrop = (e: DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(false);

    const files = e.dataTransfer.files;
    if (files.length > 0) {
      processFiles(files);
    }
  };

  const handleFileInput = (e: ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files.length > 0) {
      processFiles(e.target.files);
    }
  };

  const handleBrowseClick = () => {
    fileInputRef.current?.click();
  };

  const handleRemoveImage = (id: string) => {
    const updatedImages = images.filter((img) => img.id !== id);
    
    // If removed image was primary and there are other images, make the first one primary
    if (updatedImages.length > 0) {
      const hasPrimary = updatedImages.some((img) => img.is_primary);
      if (!hasPrimary) {
        updatedImages[0].is_primary = true;
      }
    }
    
    onImagesChange(updatedImages);
  };

  const handleSetPrimary = (id: string) => {
    const updatedImages = images.map((img) => ({
      ...img,
      is_primary: img.id === id,
    }));
    onImagesChange(updatedImages);
  };

  return (
    <div className="space-y-4">
      {/* Error Alert */}
      {error && (
        <div className="rounded-lg border border-danger bg-danger/10 p-3">
          <p className="text-sm text-danger">{error}</p>
        </div>
      )}

      {/* Upload Area */}
      <div
        className={`relative rounded-lg border-2 border-dashed transition-colors ${
          isDragging
            ? 'border-primary bg-primary/5'
            : 'border-stroke dark:border-strokedark hover:border-primary/50'
        }`}
        onDragEnter={handleDragEnter}
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onDrop={handleDrop}
      >
        <input
          ref={fileInputRef}
          type="file"
          accept="image/*"
          multiple
          onChange={handleFileInput}
          className="hidden"
        />

        <div className="p-8 text-center">
          <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
            <svg
              className="h-8 w-8 text-primary"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
              />
            </svg>
          </div>

          <h3 className="mb-1 text-lg font-semibold text-gray-900 dark:text-white">
            {isDragging ? 'Drop images here' : 'Upload product images'}
          </h3>
          <p className="mb-4 text-sm text-gray-600 dark:text-gray-400">
            Drag and drop images or{' '}
            <button
              type="button"
              onClick={handleBrowseClick}
              className="text-primary hover:underline"
            >
              browse
            </button>
          </p>
          <p className="text-xs text-gray-500 dark:text-gray-400">
            PNG, JPG, WEBP up to {maxSizeMB}MB • Maximum {maxImages} images
          </p>
        </div>
      </div>

      {/* Image Preview Grid */}
      {images.length > 0 && (
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          {images.map((image) => (
            <div
              key={image.id}
              className="relative group rounded-lg border border-stroke dark:border-strokedark overflow-hidden bg-white dark:bg-boxdark"
            >
              {/* Primary Badge */}
              {image.is_primary && (
                <div className="absolute top-2 left-2 z-10">
                  <span className="inline-flex items-center rounded-full bg-primary px-2.5 py-0.5 text-xs font-medium text-white">
                    Primary
                  </span>
                </div>
              )}

              {/* Image */}
              <div className="aspect-square">
                <img
                  src={image.preview || image.url}
                  alt="Product"
                  className="h-full w-full object-cover"
                />
              </div>

              {/* Action Overlay */}
              <div className="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                {!image.is_primary && (
                  <button
                    type="button"
                    onClick={() => handleSetPrimary(image.id)}
                    className="rounded-lg bg-white px-3 py-1.5 text-xs font-medium text-gray-900 hover:bg-gray-100"
                    title="Set as primary"
                  >
                    Set Primary
                  </button>
                )}
                <button
                  type="button"
                  onClick={() => handleRemoveImage(image.id)}
                  className="rounded-lg bg-danger px-3 py-1.5 text-xs font-medium text-white hover:bg-danger/90"
                  title="Remove image"
                >
                  Remove
                </button>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Upload More Button */}
      {images.length > 0 && images.length < maxImages && (
        <Button
          type="button"
          variant="outline"
          onClick={handleBrowseClick}
        >
          + Add More Images ({images.length}/{maxImages})
        </Button>
      )}
    </div>
  );
};

export default ImageUpload;
