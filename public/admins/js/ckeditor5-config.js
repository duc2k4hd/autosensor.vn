/**
 * CKEditor 5 Configuration và Custom Handlers
 * Thay thế TinyMCE trong hệ thống Laravel
 */

// Chỉ khởi tạo một lần
if (typeof window.CKEDITOR5_CONFIG_LOADED === 'undefined') {
    window.CKEDITOR5_CONFIG_LOADED = true;

    // License key từ builder
    const CKEDITOR_LICENSE_KEY = 'eyJhbGciOiJFUzI1NiJ9.eyJleHAiOjE4MDM1OTk5OTksImp0aSI6IjdhOGIwNjUyLTJlNzctNDE5Yy1iMTk1LWU1YTU1NTk2ZGRkOSIsImxpY2Vuc2VkSG9zdHMiOlsieGFuaHdvcmxkLnZuIiwiYXV0b3NlbnNvci52biIsIm5vYmlmYXNoaW9uLnZuIiwiKi5iYW90aW5qc2Mudm4iXSwidXNhZ2VFbmRwb2ludCI6Imh0dHBzOi8vcHJveHktZXZlbnQuY2tlZGl0b3IuY29tIiwiZGlzdHJpYnV0aW9uQ2hhbm5lbCI6WyJjbG91ZCIsImRydXBhbCJdLCJmZWF0dXJlcyI6WyJEUlVQIiwiRTJQIiwiRTJXIl0sInZjIjoiYWUzODdkNDUifQ.pcWBLNDR3jpF6MhOQbLBGZYsh6V0PNVoEXZPACFxzarn2r7X1G9hE_0ywy99-qvVHbli68wcu4LPL2Eegm0Wyw';

    /**
     * Helper function để escape HTML
     */
    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    }

/**
 * Helper function để convert relative URLs to absolute
 */
function convertRelativeToAbsolute(html) {
    if (!html) return html;
    
    return html.replace(
        /<img([^>]*?)src=["']([^"']+)["']/gi,
        (match, attrs, imageUrl) => {
            // If already absolute, keep it
            if (imageUrl.startsWith('http://') || imageUrl.startsWith('https://') || imageUrl.startsWith('//')) {
                return match;
            }
            // Convert relative to absolute
            const baseUrl = window.location.origin;
            let absoluteUrl = imageUrl;
            
            // Remove relative path prefixes
            absoluteUrl = absoluteUrl.replace(/^\.\.\/\.\.\/\.\.\//, '').replace(/^\.\.\/\.\.\//, '').replace(/^\.\.\//, '');
            
            // Ensure it starts with /
            if (!absoluteUrl.startsWith('/')) {
                absoluteUrl = '/' + absoluteUrl;
            }
            
            absoluteUrl = baseUrl + absoluteUrl;
            return `<img${attrs}src="${absoluteUrl}"`;
        }
    );
}

/**
 * Mở image cropper
 */
function openImageCropper(editor, imageSrc, imageAlt, imageElement) {
    if (!imageSrc) return;

    // Extract filename from URL
    const urlParts = imageSrc.split('/');
    const filenameWithExt = urlParts[urlParts.length - 1];
    const filenameMatch = filenameWithExt.match(/^(.+?)(\.(webp|jpg|jpeg|png|gif|svg))$/i);
    const baseFilename = filenameMatch ? filenameMatch[1] : filenameWithExt.replace(/\.[^.]+$/, '');
    const extension = filenameMatch ? filenameMatch[3] : 'webp';

    // Remove existing -size-w-h pattern if exists
    const cleanBaseFilename = baseFilename.replace(/-size-\d+-\d+$/, '');

    const originalImageData = {
        src: imageSrc,
        alt: imageAlt || '',
        element: imageElement,
        filenameWithExt: filenameWithExt,
        cleanBaseFilename: cleanBaseFilename,
        extension: extension
    };

    const modal = document.createElement('div');
    modal.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.8);display:flex;align-items:center;justify-content:center;z-index:10000;';
    modal.innerHTML = `
        <div style="background:#fff;padding:20px;border-radius:12px;max-width:90vw;max-height:90vh;overflow:auto;width:800px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                <h4 style="margin:0;">Cắt ảnh</h4>
                <button type="button" data-close-crop style="border:none;background:none;font-size:24px;cursor:pointer;color:#666;">&times;</button>
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block;margin-bottom:8px;font-weight:500;">Chọn tỷ lệ:</label>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button type="button" data-aspect-ratio="1" class="btn btn-sm btn-outline-primary">1:1 (Vuông)</button>
                    <button type="button" data-aspect-ratio="4/3" class="btn btn-sm btn-outline-primary">4:3</button>
                    <button type="button" data-aspect-ratio="16/9" class="btn btn-sm btn-outline-primary">16:9</button>
                    <button type="button" data-aspect-ratio="3/4" class="btn btn-sm btn-outline-primary">3:4 (Dọc)</button>
                    <button type="button" data-aspect-ratio="NaN" class="btn btn-sm btn-outline-primary">Tự do</button>
                </div>
            </div>
            <div style="margin-bottom:15px;">
                <img id="crop-image" src="${imageSrc}" style="max-width:100%;max-height:400px;display:block;">
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" data-close-crop>Hủy</button>
                <button type="button" class="btn btn-primary" data-crop-apply>Cắt và Lưu</button>
            </div>
        </div>
    `;

    let cropper = null;
    let currentAspectRatio = NaN;

    const closeModal = () => {
        if (cropper) {
            cropper.destroy();
        }
        document.body.removeChild(modal);
    };

    const applyCrop = async () => {
        if (!cropper) {
            alert('Vui lòng chọn tỷ lệ cắt ảnh');
            return;
        }

        const canvas = cropper.getCroppedCanvas({
            width: cropper.getData().width,
            height: cropper.getData().height,
        });

        if (!canvas) {
            alert('Không thể cắt ảnh. Vui lòng thử lại.');
            return;
        }

        const cropData = cropper.getData();
        const width = Math.round(cropData.width);
        const height = Math.round(cropData.height);

        canvas.toBlob(async (blob) => {
            if (!blob) {
                alert('Không thể tạo file ảnh. Vui lòng thử lại.');
                return;
            }

            const newFilename = `${originalImageData.cleanBaseFilename}-size-${width}-${height}.${originalImageData.extension}`;
            const formData = new FormData();
            formData.append('image', blob, newFilename);
            formData.append('original_filename', originalImageData.cleanBaseFilename + '.' + originalImageData.extension);

            try {
                const applyBtn = modal.querySelector('[data-crop-apply]');
                const originalText = applyBtn.textContent;
                applyBtn.disabled = true;
                applyBtn.textContent = 'Đang upload...';

                const cropUrl = window.cropImageUploadUrl || '/admin/products/upload-cropped-image';
                const response = await fetch(cropUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: formData,
                });

                if (!response.ok) {
                    throw new Error('Upload failed');
                }

                const data = await response.json();

                if (data.success && data.url) {
                    // Update image src trong editor
                    editor.model.change(writer => {
                        if (imageElement) {
                            writer.setAttribute(imageElement, 'src', data.url);
                            if (originalImageData.alt) {
                                writer.setAttribute(imageElement, 'alt', originalImageData.alt);
                            }
                        }
                    });

                    if (typeof markDirty === 'function') {
                        markDirty();
                    }
                    closeModal();
                } else {
                    throw new Error(data.message || 'Upload failed');
                }
            } catch (error) {
                console.error('Error uploading cropped image:', error);
                alert('Không thể upload ảnh đã cắt: ' + error.message);
                const applyBtn = modal.querySelector('[data-crop-apply]');
                applyBtn.disabled = false;
                applyBtn.textContent = 'Cắt và Lưu';
            }
        }, 'image/webp', 0.9);
    };

    modal.addEventListener('click', (e) => {
        if (e.target.matches('[data-close-crop]') || e.target === modal) {
            closeModal();
        } else if (e.target.matches('[data-aspect-ratio]')) {
            const aspectRatio = e.target.dataset.aspectRatio;
            if (aspectRatio === 'NaN') {
                currentAspectRatio = NaN;
            } else {
                currentAspectRatio = eval(aspectRatio);
            }
            if (cropper) {
                cropper.setAspectRatio(currentAspectRatio);
            }
            modal.querySelectorAll('[data-aspect-ratio]').forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline-primary');
            });
            e.target.classList.remove('btn-outline-primary');
            e.target.classList.add('btn-primary');
        } else if (e.target.matches('[data-crop-apply]')) {
            applyCrop();
        }
    });

    document.body.appendChild(modal);

    const cropImage = modal.querySelector('#crop-image');
    cropImage.onload = () => {
        if (typeof Cropper !== 'undefined') {
            cropper = new Cropper(cropImage, {
                aspectRatio: NaN,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 0.8,
                restore: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
            });
        } else {
            alert('Cropper.js chưa được tải. Vui lòng tải lại trang.');
            closeModal();
        }
    };
}



/**
 * Khởi tạo CKEditor 5 với cấu hình từ builder
 */
function initCKEditor5(selector, options = {}) {
    if (typeof window.CKEDITOR === 'undefined') {
        console.error('CKEditor 5 chưa được tải. Vui lòng kiểm tra CDN.');
        return Promise.resolve(null);
    }

    // Detect context từ URL để set media scope/folder mặc định
    const detectMediaContext = () => {
        const path = window.location.pathname;
        if (path.includes('/posts/') || path.includes('/post/')) {
            return { scope: 'client', folder: 'posts' };
        } else if (path.includes('/products/') || path.includes('/product/')) {
            return { scope: 'client', folder: 'clothes' };
        }
        // Default
        return { scope: 'client' };
    };
    
    // Lấy mediaContext từ options hoặc detect từ URL
    const mediaContext = options.mediaContext || detectMediaContext();

    const {
        ClassicEditor,
        Plugin,
        Autosave,
        Essentials,
        Paragraph,
        ImageBlock,
        ImageToolbar,
        CloudServices,
        ImageUpload,
        ImageInsertViaUrl,
        AutoImage,
        ImageTextAlternative,
        ImageCaption,
        ImageStyle,
        ImageInline,
        List,
        TodoList,
        Mention,
        ImageUtils,
        ImageEditing,
        Heading,
        Link,
        AutoLink,
        BlockQuote,
        HorizontalLine,
        CodeBlock,
        Indent,
        IndentBlock,
        Alignment,
        Style,
        GeneralHtmlSupport,
        Fullscreen,
        Emoji,
        Autoformat,
        TextTransformation,
        MediaEmbed,
        Bold,
        Italic,
        Underline,
        Strikethrough,
        Code,
        Subscript,
        Superscript,
        FontBackgroundColor,
        FontColor,
        FontFamily,
        FontSize,
        Highlight,
        Table,
        TableToolbar,
        PlainTableOutput,
        TableCaption,
        TableProperties,
        TableCellProperties,
        HtmlComment,
        SourceEditing,
        ShowBlocks,
        BalloonToolbar,
        BlockToolbar
    } = window.CKEDITOR;

    // Lưu mediaContext để dùng trong plugin
    const savedMediaContext = mediaContext;

    // Tạo plugin class kế thừa từ Plugin
    class MediaLibraryPluginClass extends Plugin {
        static get pluginName() {
            return 'MediaLibrary';
        }

        init() {
            const editor = this.editor;

            // Thêm button vào toolbar
            // Trong CKEditor 5 UMD, componentFactory.add nhận callback với locale
            // locale chứa các view classes như ButtonView
            editor.ui.componentFactory.add('mediaLibrary', locale => {
                // Debug: log để xem locale có gì
                console.log('Locale keys:', Object.keys(locale || {}));
                
                // Thử nhiều cách để lấy ButtonView
                let ButtonView = null;
                
                // Cách 1: Từ locale trực tiếp
                if (locale && locale.ButtonView) {
                    ButtonView = locale.ButtonView;
                }
                // Cách 2: Tìm trong tất cả properties của locale
                else if (locale) {
                    for (const key in locale) {
                        const value = locale[key];
                        // Tìm class có tên chứa "Button" hoặc "ButtonView"
                        if (typeof value === 'function' && (
                            key === 'ButtonView' || 
                            key.includes('Button') ||
                            (value.prototype && value.prototype.set)
                        )) {
                            // Kiểm tra xem có phải ButtonView không bằng cách thử tạo instance
                            try {
                                const testView = new value(locale);
                                if (testView && typeof testView.set === 'function' && typeof testView.on === 'function') {
                                    ButtonView = value;
                                    break;
                                }
                            } catch (e) {
                                // Không phải ButtonView
                            }
                        }
                    }
                }
                // Cách 3: Từ một button có sẵn trong editor
                if (!ButtonView) {
                    try {
                        // Tạo một button có sẵn để lấy ButtonView class
                        const existingButton = editor.ui.componentFactory.create('bold');
                        if (existingButton && existingButton.constructor) {
                            ButtonView = existingButton.constructor;
                        }
                    } catch (e) {
                        console.warn('Could not get ButtonView from existing button:', e);
                    }
                }

                // Nếu vẫn không tìm thấy, sử dụng fallback HTML button
                if (!ButtonView) {
                    console.warn('ButtonView not found, using HTML fallback button');
                    // Tạo button HTML với đúng class và style của CKEditor 5
                    const buttonElement = document.createElement('button');
                    buttonElement.className = 'ck ck-button ck-button_with-text';
                    buttonElement.setAttribute('type', 'button');
                    buttonElement.setAttribute('tabindex', '-1');
                    buttonElement.setAttribute('aria-label', 'Chèn ảnh từ thư viện');
                    buttonElement.innerHTML = '<span class="ck-button__label">🖼️ Thư viện</span>';
                    buttonElement.title = 'Chèn ảnh từ thư viện';
                    
                    const handleClick = (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        if (typeof window.openMediaPicker !== 'function') {
                            alert('Popup thư viện ảnh chưa được tải. Vui lòng F5 lại trang.');
                            return;
                        }
                        window.openMediaPicker({
                            mode: 'single',
                            scope: savedMediaContext.scope || 'client',
                            folder: savedMediaContext.folder,
                            onSelect: (file) => {
                                if (!file || !file.url) return;
                                const imgAlt   = escapeHtml(file.alt   || file.filename || file.name || '');
                                const imgTitle = escapeHtml(file.title || file.alt    || file.filename || file.name || '');
                                const rawHtml  = `<figure class="image"><picture><img src="${file.url}" alt="${imgAlt}" title="${imgTitle}"></picture>${imgTitle ? `<figcaption>${imgTitle}</figcaption>` : ''}</figure>`;
                                try {
                                    const viewFragment  = editor.data.processor.toView(rawHtml);
                                    const modelFragment = editor.data.toModel(viewFragment);
                                    editor.model.insertContent(modelFragment, editor.model.document.selection);
                                } catch (err) {
                                    // Fallback: chỉ chèn imageBlock nếu GHS không hỗ trợ
                                    editor.model.change(writer => {
                                        const imageElement = writer.createElement('imageBlock', { src: file.url, alt: imgAlt });
                                        editor.model.insertContent(imageElement, editor.model.document.selection);
                                    });
                                }
                            }
                        });
                    };
                    
                    buttonElement.addEventListener('click', handleClick);
                    
                    // Return một View-like object
                    const viewObject = {
                        render: () => buttonElement,
                        destroy: () => {
                            buttonElement.removeEventListener('click', handleClick);
                        },
                        set: () => {},
                        on: () => {},
                        bind: () => viewObject
                    };
                    return viewObject;
                }

                // Nếu tìm thấy ButtonView, sử dụng nó
                const view = new ButtonView(locale);

                view.set({
                    label: '🖼️ Thư viện',
                    tooltip: 'Chèn ảnh từ thư viện',
                    withText: true
                });

                view.on('execute', () => {
                    if (typeof window.openMediaPicker !== 'function') {
                        alert('Popup thư viện ảnh chưa được tải. Vui lòng F5 lại trang.');
                        return;
                    }

                    window.openMediaPicker({
                        mode: 'single',
                        scope: savedMediaContext.scope || 'client',
                        folder: savedMediaContext.folder,
                        onSelect: (file) => {
                            if (!file || !file.url) return;

                            const imgAlt   = escapeHtml(file.alt   || file.filename || file.name || '');
                            const imgTitle = escapeHtml(file.title || file.alt    || file.filename || file.name || '');
                            const rawHtml  = `<figure class="image"><picture><img src="${file.url}" alt="${imgAlt}" title="${imgTitle}"></picture>${imgTitle ? `<figcaption>${imgTitle}</figcaption>` : ''}</figure>`;
                            try {
                                const viewFragment  = editor.data.processor.toView(rawHtml);
                                const modelFragment = editor.data.toModel(viewFragment);
                                editor.model.insertContent(modelFragment, editor.model.document.selection);
                            } catch (err) {
                                // Fallback: chỉ chèn imageBlock nếu GHS không hỗ trợ
                                editor.model.change(writer => {
                                    const imageElement = writer.createElement('imageBlock', { src: file.url, alt: imgAlt });
                                    editor.model.insertContent(imageElement, editor.model.document.selection);
                                });
                            }
                        }
                    });
                });

                return view;
            });
        }
    }

    const defaultConfig = {
        toolbar: {
            items: [
                'undo',
                'redo',
                '|',
                'sourceEditing',
                'showBlocks',
                'fullscreen',
                '|',
                'heading',
                'style',
                '|',
                'fontSize',
                'fontFamily',
                'fontColor',
                'fontBackgroundColor',
                '|',
                'bold',
                'italic',
                'underline',
                'strikethrough',
                'subscript',
                'superscript',
                'code',
                '|',
                'emoji',
                'horizontalLine',
                'link',
                'mediaEmbed',
                'insertTable',
                'highlight',
                'blockQuote',
                'codeBlock',
                '|',
                'alignment',
                '|',
                'bulletedList',
                'numberedList',
                'todoList',
                'outdent',
                'indent'
            ],
            shouldNotGroupWhenFull: true
        },
        plugins: [
            Alignment,
            Autoformat,
            AutoImage,
            AutoLink,
            Autosave,
            BalloonToolbar,
            BlockQuote,
            BlockToolbar,
            Bold,
            CloudServices,
            Code,
            CodeBlock,
            Emoji,
            Essentials,
            FontBackgroundColor,
            FontColor,
            FontFamily,
            FontSize,
            Fullscreen,
            GeneralHtmlSupport,
            Heading,
            Highlight,
            HorizontalLine,
            ImageBlock,
            ImageCaption,
            ImageEditing,
            ImageInline,
            ImageInsertViaUrl,
            ImageStyle,
            ImageTextAlternative,
            ImageToolbar,
            ImageUpload,
            ImageUtils,
            Indent,
            IndentBlock,
            Italic,
            Link,
            List,
            MediaEmbed,
            MediaLibraryPluginClass, // Custom plugin
            Mention,
            Paragraph,
            PlainTableOutput,
            ShowBlocks,
            SourceEditing,
            Strikethrough,
            Style,
            Subscript,
            Superscript,
            Table,
            TableCaption,
            TableToolbar,
            TableProperties,
            TableCellProperties,
            TextTransformation,
            TodoList,
            Underline
        ],
        balloonToolbar: ['bold', 'italic', '|', 'link', '|', 'bulletedList', 'numberedList'],
        blockToolbar: [
            'fontSize',
            'fontColor',
            'fontBackgroundColor',
            '|',
            'bold',
            'italic',
            '|',
            'link',
            'insertTable',
            '|',
            'bulletedList',
            'numberedList',
            'outdent',
            'indent'
        ],
        fontFamily: {
            supportAllValues: true
        },
        fontSize: {
            options: [10, 12, 14, 'default', 18, 20, 22],
            supportAllValues: true
        },
        fullscreen: {
            onEnterCallback: container =>
                container.classList.add(
                    'editor-container',
                    'editor-container_classic-editor',
                    'editor-container_include-style',
                    'editor-container_include-block-toolbar',
                    'editor-container_include-fullscreen',
                    'main-container'
                )
        },
        heading: {
            options: [
                {
                    model: 'paragraph',
                    title: 'Paragraph',
                    class: 'ck-heading_paragraph'
                },
                {
                    model: 'heading1',
                    view: 'h1',
                    title: 'Heading 1',
                    class: 'ck-heading_heading1'
                },
                {
                    model: 'heading2',
                    view: 'h2',
                    title: 'Heading 2',
                    class: 'ck-heading_heading2'
                },
                {
                    model: 'heading3',
                    view: 'h3',
                    title: 'Heading 3',
                    class: 'ck-heading_heading3'
                },
                {
                    model: 'heading4',
                    view: 'h4',
                    title: 'Heading 4',
                    class: 'ck-heading_heading4'
                },
                {
                    model: 'heading5',
                    view: 'h5',
                    title: 'Heading 5',
                    class: 'ck-heading_heading5'
                },
                {
                    model: 'heading6',
                    view: 'h6',
                    title: 'Heading 6',
                    class: 'ck-heading_heading6'
                }
            ]
        },
        htmlSupport: {
            allow: [
                {
                    name: /^.*$/,
                    styles: true,
                    attributes: true,
                    classes: true
                },
                {
                    name: 'img',
                    styles: {
                        width: true,
                        height: true,
                        'max-width': true,
                        'max-height': true
                    },
                    attributes: {
                        width: true,
                        height: true
                    }
                }
            ]
        },
        image: {
            toolbar: ['toggleImageCaption', 'imageTextAlternative', '|', 'imageStyle:inline', 'imageStyle:wrapText', 'imageStyle:breakText'],
            resizeUnit: 'px',
            resizeOptions: [
                {
                    name: 'imageResize:original',
                    label: 'Original',
                    value: null
                },
                {
                    name: 'imageResize:25',
                    label: '25%',
                    value: '25'
                },
                {
                    name: 'imageResize:50',
                    label: '50%',
                    value: '50'
                },
                {
                    name: 'imageResize:75',
                    label: '75%',
                    value: '75'
                }
            ]
        },
        language: 'vi',
        licenseKey: CKEDITOR_LICENSE_KEY,
        link: {
            addTargetToExternalLinks: true,
            defaultProtocol: 'https://',
            decorators: {
                toggleDownloadable: {
                    mode: 'manual',
                    label: 'Downloadable',
                    attributes: {
                        download: 'file'
                    }
                }
            }
        },
        mention: {
            feeds: [
                {
                    marker: '@',
                    feed: []
                }
            ]
        },
        placeholder: 'Nhập nội dung...',
        style: {
            definitions: [
                {
                    name: 'Article category',
                    element: 'h3',
                    classes: ['category']
                },
                {
                    name: 'Title',
                    element: 'h2',
                    classes: ['document-title']
                },
                {
                    name: 'Subtitle',
                    element: 'h3',
                    classes: ['document-subtitle']
                },
                {
                    name: 'Info box',
                    element: 'p',
                    classes: ['info-box']
                },
                {
                    name: 'CTA Link Primary',
                    element: 'a',
                    classes: ['button', 'button--green']
                },
                {
                    name: 'CTA Link Secondary',
                    element: 'a',
                    classes: ['button', 'button--black']
                },
                {
                    name: 'Marker',
                    element: 'span',
                    classes: ['marker']
                },
                {
                    name: 'Spoiler',
                    element: 'span',
                    classes: ['spoiler']
                }
            ]
        },
        table: {
            contentToolbar: [
                'tableColumn', 
                'tableRow', 
                'mergeTableCells', 
                '|', 
                'tableProperties', 
                'tableCellProperties',
                '|',
                'toggleTableCaption'
            ]
        }
    };

    // Merge với options
    const config = { ...defaultConfig, ...options };

    // Xử lý selector: có thể là string hoặc element
    let element = null;
    if (typeof selector === 'string') {
        element = document.querySelector(selector);
    } else if (selector && selector.nodeType === 1) {
        // selector đã là một element
        element = selector;
    }

    // Lấy initial data từ textarea nếu có
    if (element && element.tagName === 'TEXTAREA' && !config.initialData) {
        let initialContent = element.value || '';
        // Convert relative URLs to absolute khi load
        initialContent = convertRelativeToAbsolute(initialContent);
        config.initialData = initialContent;
    }

    return ClassicEditor.create(element || selector, config)
        .then(editor => {
            // Tự động làm sạch nội dung khi paste từ bên ngoài vào
            editor.editing.view.document.on('clipboardInput', (evt, data) => {
                const dataTransfer = data.dataTransfer;
                const htmlContent = dataTransfer.getData('text/html');

                if (htmlContent) {
                    const cleanedHtml = cleanHtml(htmlContent);
                    const viewFragment = editor.data.processor.toView(cleanedHtml);
                    editor.model.insertContent(editor.data.toModel(viewFragment));
                    
                    // Ngăn chặn hành động paste mặc định vì mình đã chèn nội dung đã làm sạch
                    evt.stop();
                }
            });

            // Xử lý double-click và right-click trên ảnh để mở crop
            editor.editing.view.document.on('dblclick', (evt, data) => {
                const viewElement = data.target;
                if (viewElement && viewElement.is && viewElement.is('element', 'img')) {
                    const modelElement = editor.editing.mapper.toModelElement(viewElement);
                    if (modelElement && (modelElement.name === 'imageBlock' || modelElement.name === 'imageInline')) {
                        evt.stop();
                        const src = modelElement.getAttribute('src') || '';
                        const alt = modelElement.getAttribute('alt') || '';
                        openImageCropper(editor, src, alt, modelElement);
                    }
                }
            });

            editor.editing.view.document.on('contextmenu', (evt, data) => {
                const viewElement = data.target;
                if (viewElement && viewElement.is && viewElement.is('element', 'img')) {
                    const modelElement = editor.editing.mapper.toModelElement(viewElement);
                    if (modelElement && (modelElement.name === 'imageBlock' || modelElement.name === 'imageInline')) {
                        evt.stop();
                        if (data.preventDefault) {
                            data.preventDefault();
                        }
                        const src = modelElement.getAttribute('src') || '';
                        const alt = modelElement.getAttribute('alt') || '';
                        openImageCropper(editor, src, alt, modelElement);
                    }
                }
            });

            // Convert relative URLs to absolute khi lấy content
            const originalGetData = editor.getData.bind(editor);
            editor.getData = function() {
                let html = originalGetData();
                return convertRelativeToAbsolute(html);
            };

            // Thêm resize handles cho ảnh
            const addImageResizeHandles = () => {
                const editableElement = editor.ui.getEditableElement();
                if (!editableElement) {
                    console.warn('CKEditor: Editable element not found');
                    return;
                }
                
                const editorContainer = editableElement.parentElement;
                if (!editorContainer) {
                    console.warn('CKEditor: Container not found');
                    return;
                }
                
                console.log('CKEditor: Initializing resize handles...');
                
                // Ensure container has position relative
                const containerStyle = window.getComputedStyle(editorContainer);
                if (containerStyle.position === 'static') {
                    editorContainer.style.position = 'relative';
                }
                
                const removeAllHandles = () => {
                    document.querySelectorAll('.ck-image-resize-handle').forEach(h => h.remove());
                };

                const showResizeHandles = (viewElement) => {
                    removeAllHandles();
                    
                    console.log('CKEditor: showResizeHandles called', viewElement);
                    
                    if (!viewElement) {
                        console.log('CKEditor: No viewElement');
                        return;
                    }
                    
                    // Check if it's an image element
                    let imgViewElement = null;
                    
                    // Try multiple ways to detect image
                    if (viewElement.is && viewElement.is('element', 'img')) {
                        imgViewElement = viewElement;
                        console.log('CKEditor: Found img element directly');
                    } else if (viewElement.is && viewElement.is('element', 'figure')) {
                        // imageBlock is wrapped in figure, find img inside
                        const children = Array.from(viewElement.getChildren());
                        imgViewElement = children.find(child => child.is && child.is('element', 'img'));
                        console.log('CKEditor: Found img in figure', imgViewElement);
                    } else if (viewElement.parent && viewElement.parent.is) {
                        if (viewElement.parent.is('element', 'img')) {
                            imgViewElement = viewElement.parent;
                            console.log('CKEditor: Found img element from parent');
                        } else if (viewElement.parent.is('element', 'figure')) {
                            const children = Array.from(viewElement.parent.getChildren());
                            imgViewElement = children.find(child => child.is && child.is('element', 'img'));
                            console.log('CKEditor: Found img in parent figure', imgViewElement);
                        }
                    } else if (viewElement.name === 'img' || viewElement.tagName === 'IMG') {
                        // Fallback: check name or tagName
                        imgViewElement = viewElement;
                        console.log('CKEditor: Found img by name/tagName');
                    }
                    
                    if (!imgViewElement) {
                        console.log('CKEditor: imgViewElement not found', viewElement.name || viewElement.tagName || viewElement.constructor?.name);
                        return;
                    }
                    
                    if (!imgViewElement) {
                        console.log('CKEditor: imgViewElement is null');
                        return;
                    }
                    
                    // Try multiple ways to get model element
                    let modelElement = editor.editing.mapper.toModelElement(imgViewElement);
                    console.log('CKEditor: modelElement from mapper', modelElement?.name);
                    
                    // If not found, try to get from selection
                    if (!modelElement) {
                        const selection = editor.model.document.selection;
                        const selectedElement = selection.getSelectedElement();
                        if (selectedElement && (selectedElement.name === 'imageBlock' || selectedElement.name === 'imageInline')) {
                            modelElement = selectedElement;
                            console.log('CKEditor: modelElement from selection', modelElement.name);
                        }
                    }
                    
                    // If still not found, try parent
                    if (!modelElement && imgViewElement.parent) {
                        modelElement = editor.editing.mapper.toModelElement(imgViewElement.parent);
                        console.log('CKEditor: modelElement from parent', modelElement?.name);
                    }
                    
                    if (!modelElement) {
                        console.warn('CKEditor: modelElement not found');
                        return;
                    }
                    
                    if (modelElement.name !== 'imageBlock' && modelElement.name !== 'imageInline') {
                        console.log('CKEditor: Not imageBlock/imageInline, got:', modelElement.name);
                        return;
                    }
                    
                    // Get image DOM element
                    const domElement = editor.editing.view.domConverter.mapViewToDom(imgViewElement);
                    console.log('CKEditor: domElement', domElement);
                    
                    if (!domElement) {
                        console.warn('CKEditor: DOM element not found for image');
                        return;
                    }
                    
                    console.log('CKEditor: Showing resize handles for image', domElement);
                    
                    // Use requestAnimationFrame to ensure DOM is ready
                    requestAnimationFrame(() => {
                        const rect = domElement.getBoundingClientRect();
                        const containerRect = editorContainer.getBoundingClientRect();
                        
                        if (rect.width === 0 || rect.height === 0) {
                            console.warn('CKEditor: Image has zero dimensions', rect);
                            return;
                        }
                        
                        console.log('CKEditor: Image rect', rect, 'Container rect', containerRect);
                        
                        // Create resize handles at 4 corners
                        const corners = [
                            { pos: 'nw', cursor: 'nwse-resize', x: rect.left - containerRect.left, y: rect.top - containerRect.top },
                            { pos: 'ne', cursor: 'nesw-resize', x: rect.right - containerRect.left, y: rect.top - containerRect.top },
                            { pos: 'sw', cursor: 'nesw-resize', x: rect.left - containerRect.left, y: rect.bottom - containerRect.top },
                            { pos: 'se', cursor: 'nwse-resize', x: rect.right - containerRect.left, y: rect.bottom - containerRect.top }
                        ];
                        
                        corners.forEach(corner => {
                            const handle = document.createElement('div');
                            handle.className = `ck-image-resize-handle ck-image-resize-handle-${corner.pos}`;
                            handle.setAttribute('data-handle-pos', corner.pos);
                            handle.style.cssText = `
                                position: absolute;
                                width: 16px;
                                height: 16px;
                                background: #4a90e2;
                                border: 3px solid white;
                                border-radius: 50%;
                                cursor: ${corner.cursor};
                                z-index: 10001;
                                pointer-events: all;
                                left: ${corner.x - 8}px;
                                top: ${corner.y - 8}px;
                                box-shadow: 0 2px 8px rgba(0,0,0,0.4);
                                transition: transform 0.1s ease;
                            `;
                            
                            handle.addEventListener('mouseenter', () => {
                                handle.style.transform = 'scale(1.3)';
                                handle.style.background = '#357abd';
                            });
                            handle.addEventListener('mouseleave', () => {
                                handle.style.transform = 'scale(1)';
                                handle.style.background = '#4a90e2';
                            });
                            
                            let isResizing = false;
                            let startX, startY, startWidth, startHeight, aspectRatio;
                            
                            handle.addEventListener('mousedown', (e) => {
                                e.preventDefault();
                                e.stopPropagation();
                                isResizing = true;
                                startX = e.clientX;
                                startY = e.clientY;
                                const currentRect = domElement.getBoundingClientRect();
                                startWidth = currentRect.width;
                                startHeight = currentRect.height;
                                aspectRatio = startWidth / startHeight;
                                
                                const onMouseMove = (e) => {
                                    if (!isResizing) return;
                                    
                                    const deltaX = e.clientX - startX;
                                    const deltaY = e.clientY - startY;
                                    
                                    let newWidth = startWidth;
                                    let newHeight = startHeight;
                                    
                                    if (corner.pos === 'se') {
                                        newWidth = Math.max(50, startWidth + deltaX);
                                        newHeight = newWidth / aspectRatio;
                                    } else if (corner.pos === 'sw') {
                                        newWidth = Math.max(50, startWidth - deltaX);
                                        newHeight = newWidth / aspectRatio;
                                    } else if (corner.pos === 'ne') {
                                        newWidth = Math.max(50, startWidth + deltaX);
                                        newHeight = newWidth / aspectRatio;
                                    } else if (corner.pos === 'nw') {
                                        newWidth = Math.max(50, startWidth - deltaX);
                                        newHeight = newWidth / aspectRatio;
                                    }
                                    
                                    // Update DOM
                                    domElement.style.width = Math.round(newWidth) + 'px';
                                    domElement.style.height = Math.round(newHeight) + 'px';
                                    
                                    // Update model
                                    editor.model.change(writer => {
                                        writer.setAttribute(modelElement, 'width', Math.round(newWidth));
                                        writer.setAttribute(modelElement, 'height', Math.round(newHeight));
                                    });
                                    
                                    // Update handle positions
                                    const newRect = domElement.getBoundingClientRect();
                                    const newContainerRect = editorContainer.getBoundingClientRect();
                                    document.querySelectorAll('.ck-image-resize-handle').forEach(h => {
                                        const pos = h.getAttribute('data-handle-pos');
                                        if (!pos) return;
                                        let x, y;
                                        if (pos === 'nw') {
                                            x = newRect.left - newContainerRect.left - 8;
                                            y = newRect.top - newContainerRect.top - 8;
                                        } else if (pos === 'ne') {
                                            x = newRect.right - newContainerRect.left - 8;
                                            y = newRect.top - newContainerRect.top - 8;
                                        } else if (pos === 'sw') {
                                            x = newRect.left - newContainerRect.left - 8;
                                            y = newRect.bottom - newContainerRect.top - 8;
                                        } else if (pos === 'se') {
                                            x = newRect.right - newContainerRect.left - 8;
                                            y = newRect.bottom - newContainerRect.top - 8;
                                        }
                                        h.style.left = x + 'px';
                                        h.style.top = y + 'px';
                                    });
                                };
                                
                                const onMouseUp = () => {
                                    isResizing = false;
                                    document.removeEventListener('mousemove', onMouseMove);
                                    document.removeEventListener('mouseup', onMouseUp);
                                    // Refresh handles after resize
                                    setTimeout(() => showResizeHandles(imgViewElement), 50);
                                };
                                
                                document.addEventListener('mousemove', onMouseMove);
                                document.addEventListener('mouseup', onMouseUp);
                            });
                            
                            editorContainer.appendChild(handle);
                            console.log('CKEditor: Added resize handle', corner.pos, 'at', corner.x, corner.y);
                        });
                    });
                };

                // Listen to selection changes
                editor.model.document.selection.on('change', () => {
                    const selection = editor.model.document.selection;
                    const selectedElement = selection.getSelectedElement();
                    
                    // console.log('CKEditor: Selection changed', selectedElement?.name);
                    
                    if (selectedElement && (selectedElement.name === 'imageBlock' || selectedElement.name === 'imageInline')) {
                        const viewElement = editor.editing.mapper.toViewElement(selectedElement);
                        console.log('CKEditor: View element from selection', viewElement);
                        if (viewElement) {
                            setTimeout(() => showResizeHandles(viewElement), 100);
                        }
                    } else {
                        removeAllHandles();
                    }
                });
                
                // Also listen to clicks for better UX
                editor.editing.view.document.on('click', (evt, data) => {
                    const viewElement = data.target;
                    console.log('CKEditor: Click detected', viewElement?.name || viewElement?.tagName || viewElement?.constructor?.name);
                    
                    // Try to find image element
                    if (viewElement && viewElement.is) {
                        if (viewElement.is('element', 'img') || viewElement.is('element', 'figure')) {
                            setTimeout(() => showResizeHandles(viewElement), 100);
                        } else {
                            // Try to find img in parent chain
                            let current = viewElement;
                            for (let i = 0; i < 5 && current; i++) {
                                if (current.is && (current.is('element', 'img') || current.is('element', 'figure'))) {
                                    setTimeout(() => showResizeHandles(current), 100);
                                    break;
                                }
                                current = current.parent;
                            }
                        }
                    }
                });
                
                // Remove handles when clicking outside
                editableElement.addEventListener('click', (e) => {
                    if (!e.target.closest('img')) {
                        removeAllHandles();
                    }
                }, true);
                
                console.log('CKEditor: Resize handles initialized');
            };
            
            // Wait a bit for editor to be fully ready
            setTimeout(() => {
                addImageResizeHandles();
            }, 300);

            // Store editor instance
            if (element) {
                element._ckeditorInstance = editor;
            }

            return editor;
        })
        .catch(error => {
            console.error('Error initializing CKEditor 5:', error);
            return null;
        });
}

    /**
     * Hàm làm sạch HTML: xóa class, giữ style, giữ href (<a>), giữ src/alt/title (<img>)
     * Xóa các thẻ rác, thẻ trống hoặc thẻ lồng nhau không có nội dung.
     */
    function cleanHtml(html) {
        if (!html) return html;

        // 0. Làm sạch sơ bộ: Xóa sạch comment (bao gồm [if !mso]...), XML và styles
        html = html.replace(/<!(?:--[\s\S]*?--\s*)?>/gi, ''); // Xóa sạch mọi loại comment <!-- ... -->
        html = html.replace(/<xml[\s\S]*?<\/xml>/gi, ''); // Xóa MS Word XML blocks
        html = html.replace(/<style[\s\S]*?<\/style>/gi, ''); // Xóa Internal Style block
        html = html.replace(/<script[\s\S]*?<\/script>/gi, ''); // Xóa Script
        html = html.replace(/<meta[\s\S]*?>/gi, ''); // Xóa Meta tags
        html = html.replace(/<link[\s\S]*?>/gi, ''); // Xóa Link tags
        html = html.replace(/<\?xml[\s\S]*?\?>/gi, ''); // Xóa XML declaration

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;

        // 1. Unwrap thẻ Namespace (giữ nội dung bên trong thay vì xóa cả thẻ con)
        let allElements = Array.from(tempDiv.getElementsByTagName('*'));
        for (let i = allElements.length - 1; i >= 0; i--) {
            const el = allElements[i];
            const tag = el.tagName.toUpperCase();
            if (tag.includes(':') || tag.startsWith('M:') || tag === 'ST1:PLACE') {
                while (el.firstChild) {
                    el.parentNode.insertBefore(el.firstChild, el);
                }
                el.parentNode.removeChild(el);
            }
        }

        // 2. Làm sạch thuộc tính: chỉ giữ style, href, src, title, alt
        allElements = Array.from(tempDiv.getElementsByTagName('*'));
        for (let i = 0; i < allElements.length; i++) {
            const el = allElements[i];
            const tag = el.tagName.toUpperCase();
            const attrs = Array.from(el.attributes);
            
            for (const attr of attrs) {
                const name = attr.name.toLowerCase();
                let keep = false;
                
                if (name === 'style') keep = true;
                if (tag === 'A' && name === 'href') keep = true;
                if (tag === 'IMG' && (name === 'src' || name === 'title' || name === 'alt')) keep = true;
                
                if (!keep) {
                    el.removeAttribute(attr.name);
                }
            }
        }

        // 3. Xóa các cặp thẻ rỗng lồng nhau
        let changed = true;
        while (changed) {
            changed = false;
            const currentElements = Array.from(tempDiv.getElementsByTagName('*'));
            for (let i = currentElements.length - 1; i >= 0; i--) {
                const el = currentElements[i];
                const tag = el.tagName.toUpperCase();
                
                if (['IMG', 'BR', 'HR', 'IFRAME', 'VIDEO', 'INPUT', 'EMBED', 'TD', 'TH'].includes(tag)) continue;

                const content = el.innerHTML.replace(/&nbsp;/g, '').replace(/\s/g, '').trim();
                if (content === '') {
                    el.parentNode.removeChild(el);
                    changed = true;
                }
            }
        }

        return tempDiv.innerHTML;
    }

    // Export cho global use
    window.cleanHtml = cleanHtml;
    window.initCKEditor5 = initCKEditor5;
    window.CKEDITOR_LICENSE_KEY = CKEDITOR_LICENSE_KEY;
    window.openImageCropper = openImageCropper;
    window.convertRelativeToAbsolute = convertRelativeToAbsolute;
    window.escapeHtml = escapeHtml;
}
