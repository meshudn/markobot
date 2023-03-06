<!DOCTYPE html>
<html>
  <head>
    <script src="https://unpkg.com/konva@8.4.2/konva.min.js"></script>
    <meta charset="utf-8" />
    <title>MarkoBot</title>
    <style>
      body {
        margin: 0;
        padding: 0;
        overflow: hidden;
        background-color: #f0f0f0;
      }
      select {
    
        left: 10px;
      }
      .card{
        width: 300px;
        height: 400px;
        background: #fff;
        border: 1px solid #eee;
        margin: 0 auto;
        text-align: center;
        margin-top: 40px;
      }
      .lower-part{
        z-index: 99;
      }
      .button-area{
        margin: 0 auto;
        margin-top: 50px;
        height: 200px;
        width: 200px;
      }
      .title{
        text-align: center;
        color: "sky-blue"; 
      }
    </style>
  </head>

  <body>
    <h4 class="title">MarkoBot</h1>
     <div id="container" class="card"></div>

     <div class="button-area">
        <div id="buttons"><button id="save">Save as image (Png)</button></div>
        <div id="buttons"><button id="pdfsave">Save as Pdf</button></div>
        <br>
        <p>Image clip method: </p>
        <select id="clip">
        <option value="left-top" selected>left-top</option>
        <option value="center-top">center-top</option>
        <option value="right-top">right-top</option>
        <option value="--">--</option>
        <option value="left-middle">left-middle</option>
        <option value="center-middle">center-middle</option>
        <option value="right-middle">right-middle</option>
        <option value="--">--</option>
        <option value="left-bottom">left-bottom</option>
        <option value="center-bottom">center-bottom</option>
        <option value="right-bottom">right-bottom</option>
        </select>
     </div>
     <script src="https://unpkg.com/pdf-lib@1.4.0"></script>
    <script src="https://unpkg.com/downloadjs@1.4.7"></script>

    <script>
      const stage = new Konva.Stage({
        container: 'container',
        width: 300,
        height: 400,
      });

      const layer = new Konva.Layer();
      stage.add(layer);
      const textLayer = new Konva.Layer();
      stage.add(textLayer);

      // function to calculate crop values from source image, its visible size and a crop strategy
      function getCrop(image, size, clipPosition = 'center-middle') {
        const width = size.width;
        const height = size.height;
        const aspectRatio = width / height;

        let newWidth;
        let newHeight;

        const imageRatio = image.width / image.height;

        if (aspectRatio >= imageRatio) {
          newWidth = image.width;
          newHeight = image.width / aspectRatio;
        } else {
          newWidth = image.height * aspectRatio;
          newHeight = image.height;
        }

        let x = 0;
        let y = 0;
        if (clipPosition === 'left-top') {
          x = 0;
          y = 0;
        } else if (clipPosition === 'left-middle') {
          x = 0;
          y = (image.height - newHeight) / 2;
        } else if (clipPosition === 'left-bottom') {
          x = 0;
          y = image.height - newHeight;
        } else if (clipPosition === 'center-top') {
          x = (image.width - newWidth) / 2;
          y = 0;
        } else if (clipPosition === 'center-middle') {
          x = (image.width - newWidth) / 2;
          y = (image.height - newHeight) / 2;
        } else if (clipPosition === 'center-bottom') {
          x = (image.width - newWidth) / 2;
          y = image.height - newHeight;
        } else if (clipPosition === 'right-top') {
          x = image.width - newWidth;
          y = 0;
        } else if (clipPosition === 'right-middle') {
          x = image.width - newWidth;
          y = (image.height - newHeight) / 2;
        } else if (clipPosition === 'right-bottom') {
          x = image.width - newWidth;
          y = image.height - newHeight;
        } else if (clipPosition === 'scale') {
          x = 0;
          y = 0;
          newWidth = width;
          newHeight = height;
        } else {
          console.error(
            new Error('Unknown clip position property - ' + clipPosition)
          );
        }

        return {
          cropX: x,
          cropY: y,
          cropWidth: newWidth,
          cropHeight: newHeight,
        };
      }

      // function to apply crop
      function applyCrop(pos) {
        const img = layer.findOne('.image');
        img.setAttr('lastCropUsed', pos);
        const crop = getCrop(
          img.image(),
          { width: img.width(), height: img.height() },
          pos
        );
        img.setAttrs(crop);
      }
      function applyCrop2(pos) {
        const img = textLayer.findOne('.image');
        img.setAttr('lastCropUsed', pos);
        const crop = getCrop(
          img.image(),
          { width: img.width(), height: img.height() },
          pos
        );
        img.setAttrs(crop);
      }

      Konva.Image.fromURL(
        'https://konvajs.org/assets/darth-vader.jpg',
        (img) => {
          img.setAttrs({
            width: 300,
            height: 100,
            x: 80,
            y: 100,
            name: 'image',
            draggable: true,
          });
          layer.add(img);
          // apply default left-top crop
          applyCrop('center-middle');

          const tr = new Konva.Transformer({
            nodes: [img],
            keepRatio: false,
            boundBoxFunc: (oldBox, newBox) => {
              if (newBox.width < 10 || newBox.height < 10) {
                return oldBox;
              }
              return newBox;
            },
          });

          layer.add(tr);

          img.on('transform', () => {
            // reset scale on transform
            img.setAttrs({
              scaleX: 1,
              scaleY: 1,
              width: img.width() * img.scaleX(),
              height: img.height() * img.scaleY(),
            });
            applyCrop(img.getAttr('lastCropUsed'));
          });
        }
      );

      document.querySelector('#clip').onchange = (e) => {
        applyCrop(e.target.value);
      };

       // function from https://stackoverflow.com/a/15832662/512042
       function downloadURI(uri, name) {
        var link = document.createElement('a');
        link.download = name;
        link.href = uri;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        delete link;
      }

      document.getElementById('save').addEventListener(
        'click',
        function () {
          var dataURL = stage.toDataURL({ pixelRatio: 2 });
          downloadURI(dataURL, 'stage.png');
        },
        false
      );
      document.getElementById('pdfsave').addEventListener(
        'click',
        function () {
          var dataURL = stage.toDataURL({ pixelRatio: 2 });
          embedImages(dataURL);
        },
        false
      );
   

      Konva.Image.fromURL(
        'image.png',
        (img) => {
          img.setAttrs({
            width: 300,
            height: 100,
            x: 80,
            y: 100,
            name: 'image',
            draggable: true,
          });
          textLayer.add(img);
          // apply default left-top crop
          applyCrop2('center-middle');

          

          img.on('transform', () => {
            // reset scale on transform
            const tr = new Konva.Transformer({
            nodes: [img],
            keepRatio: false,
            boundBoxFunc: (oldBox, newBox) => {
              if (newBox.width < 10 || newBox.height < 10) {
                return oldBox;
              }
              return newBox;
            },
          });

          textLayer.add(tr);

            img.setAttrs({
              scaleX: 1,
              scaleY: 1,
              width: img.width() * img.scaleX(),
              height: img.height() * img.scaleY(),
            });
            applyCrop2(img.getAttr('lastCropUsed'));
          });
        }
      );

    </script>

<script>
    const { PDFDocument } = PDFLib

    async function embedImages(data) {

      // Create a new PDFDocument
      const pdfDoc = await PDFDocument.create()

      // Embed the JPG image bytes and PNG image bytes
      const jpgImage = await pdfDoc.embedPng(data)

      // Get the width/height of the JPG image scaled down to 25% of its original size
      const jpgDims = jpgImage.scale(1)

      // Add a blank page to the document
      const page = pdfDoc.addPage()

      // Draw the JPG image in the center of the page
      page.drawImage(jpgImage, {
        x: page.getWidth() / 2 - jpgDims.width / 2,
        y: page.getHeight() / 2 - jpgDims.height / 2,
        width: jpgDims.width,
        height: jpgDims.height,
      })

      // Serialize the PDFDocument to bytes (a Uint8Array)
      const pdfBytes = await pdfDoc.save()

			// Trigger the browser to download the PDF document
      download(pdfBytes, "markoBot-pdf.pdf", "application/pdf");
    }
  </script>
  </body>
</html>