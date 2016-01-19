<?php


namespace Base\Image;

include_once(dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'functions.php');

class Image
{
    // Path properties
    private $imgDir;
    private $cacheDir;
    private $pathToImage;

    // Misc
    private $verbose;
    private $saveImgAs;
    private $quality;
    private $ignoreCache;
    private $imageToSave;
    private $sharpen;

    // Effect properties
    private $effect;

    // Properties about the image
    private $type;
    private $mime;
    private $width;
    private $height;
    private $imgInfo;
    private $filesize;
    private $attribute;

    // Properties about the filename
    private $cacheFileName;
    private $fileExtension;
    private $imageSrcName;

    private $gmdate;

    // Properties for cropping/resizing
    private $cropToFit;
    private $cropWidth;
    private $cropHeight;
    private $newWidth;
    private $newHeight;
    const MAX_WIDTH = 3000;
    const MAX_HEIGHT = 3000;







    public function __construct ($getRequest, $imgDirectory, $cacheDirectory)
    {
        $this->setVariables($getRequest, $imgDirectory, $cacheDirectory);
        $this->controlPathAndName();

        if ($this->newHeight || $this->newWidth) { $this->setNewWidthOrHeight(); }

        $this->setCacheInformation();

        if ($this->verbose) { $this->verboseMode(); }

        // Open image to work with
        $this->openImage();

        if ($this->effect) { $this->addEffect(); }

        // Crop or resize or keep original size of image
        ($this->cropToFit) ? $this->cropImage() : $this->resizeImage();


        $this->saveOrOutputImage();
    }




    private function addEffect()
    {
        switch ($this->effect) {
            case 'grayscale':
                imagefilter($this->imageToSave, IMG_FILTER_GRAYSCALE);
                break;
        }
    }





    private function sharpenImage($image)
    {
        $matrix = array(
            array(-1,-1,-1,),
            array(-1,16,-1,),
            array(-1,-1,-1,)
        );

        $divisor = 8;
        $offset = 0;

        imageconvolution($image, $matrix, $divisor, $offset);
        return $image;
    }







    private function cropImage()
    {
        if($this->verbose) { verbose("Resizing, crop to fit."); }

        $cropX = round(($this->width - $this->cropWidth) / 2);
        $cropY = round(($this->height - $this->cropHeight) / 2);

        $imageResized = imagecreatetruecolor($this->newWidth, $this->newHeight);

        imagecopyresampled($imageResized, $this->imageToSave, 0, 0, $cropX, $cropY, $this->newWidth, $this->newHeight, $this->cropWidth, $this->cropHeight);

        $this->imageToSave = $imageResized;
        $this->width = $this->newWidth;
        $this->height = $this->newHeight;
    }









    private function resizeImage()
    {
        if($this->verbose) { verbose("Resizing, new height and/or width."); }

        $imageResized = imagecreatetruecolor($this->newWidth, $this->newHeight);

        imagecopyresampled($imageResized, $this->imageToSave, 0, 0, 0, 0, $this->newWidth, $this->newHeight, $this->width, $this->height);

        $this->imageToSave = $imageResized;
        $this->width  = $this->newWidth;
        $this->height = $this->newHeight;
    }









    private function saveOrOutputImage()
    {
        $imageModifiedTime = filemtime($this->pathToImage);
        $cacheModifiedTime = is_file($this->cacheFileName) ? filemtime($this->cacheFileName) : null;

        if (!$this->ignoreCache && is_file($this->cacheFileName) && $imageModifiedTime < $cacheModifiedTime) {
            if($this->verbose) { echo verbose("Cache file is valid, output it."); }
            $this->outputImage();
        }
        else {
            if ($this->verbose) { echo verbose("Cache wasn't allowed, please recache"); }
            if ($this->verbose) { echo verbose("File extension: " . $this->fileExtension); }

            $this->saveImage();

            if($this->verbose) {
                clearstatcache();
                $cacheFilesize = filesize($this->cacheFileName);
                echo verbose('Cached image size: '.$cacheFilesize.' bytes.');
                echo verbose('Cached file has this ' . round($cacheFilesize/$this->filesize*100) . '% compared to original.');
            }

            $this->outputImage();
        }
    }









    private function openImage()
    {
        switch ($this->fileExtension)
        {
            case 'jpg':
            case 'jpeg':
                $this->imageToSave = imagecreatefromjpeg($this->pathToImage);
                if ($this->verbose) { echo verbose('Opened the image as a JPEG image.'); }
                break;

            case 'png':
                $this->imageToSave = imagecreatefrompng($this->pathToImage);
                imagealphablending($this->imageToSave, false);
                imagesavealpha($this->imageToSave, true);
                if($this->verbose) { echo verbose('Opened the image as a PNG image.'); }
                break;

            case 'gif':
                $this->imageToSave = imagecreatefromgif($this->pathToImage);
                if ($this->verbose) { echo verbose('Opened the image as a GIF image.'); }
                break;

            default:
                errorPage('No support for this kind of image');
                break;
        }
    }











    private function saveImage()
    {
        if($this->sharpen) {
            $this->imageToSave = $this->sharpenImage($this->imageToSave);
        }

        // Save the image
        switch($this->saveImgAs) {
            case 'jpeg':
            case 'jpg':
                if($this->verbose) { echo verbose('Saving with the quality = '.$this->quality.'.'); }
                imagejpeg($this->imageToSave, $this->cacheFileName, $this->quality);
                break;

            case 'png':
                if($this->verbose) { echo verbose('Saving image as PNG.'); }
                imagealphablending($this->imageToSave, false);
                imagesavealpha($this->imageToSave, true);
                imagepng($this->imageToSave, $this->cacheFileName);
                break;

            case 'gif':
                if ($this->verbose) { echo verbose('Saving image as GIF'); }
                imagegif($this->imageToSave, $this->cacheFileName);
                break;

            default:
                errorMessage('No support for this kind of image.');
                break;
        }
    }








    private function setCacheInformation()
    {
        // Get pathinfo about the image and extension
        $parts = pathinfo($this->pathToImage);
        $this->fileExtension = $parts['extension'];

        // Add attributes to the name of the image
        $this->saveImgAs = is_null($this->saveImgAs) ? $this->fileExtension : $this->saveImgAs;
        $quality_ = is_null($this->quality) ? null : '_q' . $this->quality;
        $cropToFit_ = is_null($this->cropToFit) ? null : "_cf";
        $sharpen_ = is_null($this->sharpen) ? null : "_s";

        // Finalize the name for the image to save as cached image
        $dirName = preg_replace('/\//', '-', dirname($this->imageSrcName));
        $cacheFileName = $this->cacheDir . "-{$dirName}-{$parts['filename']}_{$this->newWidth}_{$this->newHeight}{$quality_}{$cropToFit_}{$sharpen_}.{$this->saveImgAs}";
        $this->cacheFileName = preg_replace('/^a-zA-Z0-9\.-_/', '', $cacheFileName);
    }







    private function verboseMode()
    {
        // Information to output if verbose-mode is active
        if ($this->verbose) {
            $query = array();
            parse_str($_SERVER['QUERY_STRING'], $query);
            unset($query['verbose']);
            $url = '?' . http_build_query($query);

            echo '
                <html lang="en">
                <meta charset="UTF-8"/>
                    <title>img.php verbose mode</title>
                    <h1>Verbose mode</h1>
                    <p>
                        <a href="'.$url.'"><code>'.$url.'</code></a><br>
                        <img src="'.$url.'" />
                    </p>';

            echo verbose("Image file: {$this->pathToImage}");
            echo verbose("Image information: " . print_r($this->imgInfo, true));
            echo verbose("Image width x height (type): {$this->width} x {$this->height} ({$this->type}).");
            echo verbose("Image file size: {$this->filesize} bytes.");
            echo verbose("Image mime type: {$this->mime}.");
            echo verbose('Cache filename: ' . $this->cacheFileName);

            echo verbose('Memory peak: ' . round(memory_get_peak_usage() /1024/1024) . 'M');
            echo verbose('Memory limit: ' . ini_get('memory_limit'));
            echo verbose('Time is ' . $this->gmdate . ' GMT');
        }
    }








    private function setVariables($fromGet, $imgDir, $cacheDir)
    {
        // Set basic variables
        $this->imageSrcName = isset($fromGet['src']) ? $fromGet['src'] : errorMessage('You must name SRC-directory.');
        $this->verbose = isset($fromGet['verbose']) ? true : null;
        $this->saveImgAs = isset($fromGet['save-as']) ? $fromGet['save-as'] : null;
        $this->quality = isset($fromGet['quality']) ? $fromGet['quality'] : 60;
        $this->ignoreCache = isset($fromGet['no-cache']) ? true : null;
        $this->cropToFit = isset($fromGet['crop-to-fit']) ? true : null;
        $this->sharpen = isset($fromGet['sharpen']) ? true : null;
        $this->effect = isset($fromGet['effect']) ? $fromGet['effect'] : null;

        // Image and Cache directories
        $this->imgDir = $imgDir;
        $this->cacheDir = $cacheDir;

        // Set the direct path to the image
        $this->pathToImage = realpath($this->imgDir . $this->imageSrcName);

        // Set the filesize
        $this->filesize = filesize($this->pathToImage);

        // Check if its an image and set info variables.
        $this->imgInfo = list($this->width, $this->height, $this->type, $this->attribute) = getimagesize($this->pathToImage);
        !empty($this->imgInfo) or errorMessage("Doesn't seem to be a image.");
        $this->mime = $this->imgInfo['mime'];

        $this->newWidth   = isset($fromGet['width'])   ? $fromGet['width']    : $this->width;
        $this->newHeight  = isset($fromGet['height'])  ? $fromGet['height']   : $this->height;
    }







    private function controlPathAndName()
    {
        // Check filename
        preg_match('#^[a-z0-9A-Z-_\.\/]+$#', $this->imageSrcName) or errorMessage('Filename contains invalid characters.');

        is_dir($this->imgDir) or errorMessage("Image path isn't valid.");
        is_writable($this->cacheDir) or errorMessage("Cache path isn't writable");

        substr_compare($this->imgDir, $this->pathToImage, 0, strlen($this->imgDir)) == 0 or errorMessage("Image path isnt directly below given path.");
        is_null($this->saveImgAs) or in_array($this->saveImgAs, array('png', 'jpg', 'jpeg', 'gif')) or errorMessage("Not valid to save image in given extension");
        is_null($this->quality) or (is_numeric($this->quality) and $this->quality > 0 and $this->quality <= 100) or errorMessage("Quality only allowed from 1 to 100");

        is_null($this->newWidth) or (is_numeric($this->newWidth) and $this->newWidth > 0 and $this->newWidth <= self::MAX_WIDTH) or errorMessage('Too much width! Max: '.MAX_WIDTH);
        is_null($this->newHeight) or (is_numeric($this->newHeight) and $this->newHeight > 0 and $this->newHeight <= self::MAX_HEIGHT) or errorMessage('Too much height! Max: '.MAX_HEIGHT);

        is_null($this->cropToFit) or ($this->cropToFit and $this->newWidth and $this->newHeight) or errorMessage("Cropping requires both Width and Height attributes");
    }







    private function setNewWidthOrHeight()
    {
        $aspectRatio = $this->width / $this->height;

        if($this->cropToFit && $this->newWidth && $this->newHeight) {
            $targetRatio = $this->newWidth / $this->newHeight;
            $this->cropWidth   = $targetRatio > $aspectRatio ? $this->width : round($this->height * $targetRatio);
            $this->cropHeight  = $targetRatio > $aspectRatio ? round($this->width  / $targetRatio) : $this->height;
            if($this->verbose) { verbose("Cropping to fit: {$this->newWidth}x{$this->newHeight}. Dimensions: {$this->cropWidth}x{$this->cropHeight}."); }
        }
        elseif($this->newWidth && !$this->newHeight) {
            $this->newHeight = round($this->newWidth / $aspectRatio);
            if($this->verbose) { verbose("Width calculated to: {$this->newWidth}, Height is calculated to: {$this->newHeight}."); }
        }
        elseif(!$this->newWidth && $this->newHeight) {
            $this->newWidth = round($this->newHeight * $aspectRatio);
            if($this->verbose) { verbose("Height is calculated to: {$this->newHeight}, Width calculated to: {$this->newWidth}."); }
        }
        elseif($this->newWidth && $this->newHeight) {
            $ratioWidth  = $this->width  / $this->newWidth;
            $ratioHeight = $this->height / $this->newHeight;
            $ratio = ($ratioWidth > $ratioHeight) ? $ratioWidth : $ratioHeight;
            $this->newWidth  = round($this->width  / $ratio);
            $this->newHeight = round($this->height / $ratio);
            if($this->verbose) { verbose("Both new Height and Width given. Keeping ratio: {$this->newWidth}x{$this->newHeight}."); }
        }
        else {
            $this->newWidth = $this->width;
            $this->newHeight = $this->height;
            if($this->verbose) { verbose("Keeping original Width and Height"); }
        }
    }








    public function outputImage()
    {
        $lastModified = filemtime($this->cacheFileName);
        $this->gmdate = gmdate("D, d M Y H:i:s", $lastModified);


        if (!$this->verbose) header('Last-Modified: ' . $this->gmdate . ' GMT');
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $lastModified) {
            if ($this->verbose) { echo verbose("Would send 304 not modified, but it's verbose mode."); exit; }
            header('HTTP/1.0 304 Not Modified');
        } else {
            if ($this->verbose) { echo verbose('Would send header to deliver image: ' . $this->gmdate . ' GMT. Though, verbose mode'); exit; }
            header('Content-type: ' . $this->mime);
            readfile($this->cacheFileName);
        }
        exit;
    }
}
