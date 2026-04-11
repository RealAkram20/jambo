function openFileManager(selector) {
    // Create modal container
    const modalHtml = `
        <div class="modal fade" id="fileManagerModal" tabindex="-1" role="dialog" aria-labelledby="fileManagerModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="fileManagerModalLabel">File Manager</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <!-- Left Sidebar -->
                            <div class="col-12 col-md-3">
                                <div class="list-group">
                                    <a href="#" class="list-group-item list-group-item-action active" data-folder="movie-thumb">
                                        <i class="fas fa-folder"></i> Movie Thumbnails
                                    </a>
                                    <a href="#" class="list-group-item list-group-item-action" data-folder="show-thumb">
                                        <i class="fas fa-folder"></i> Show Thumbnails
                                    </a>
                                    <a href="#" class="list-group-item list-group-item-action" data-folder="episode-thumb">
                                        <i class="fas fa-folder"></i> Episode Thumbnails
                                    </a>
                                </div>
                                <div class="mt-3">
                                    <button class="btn btn-primary w-100" id="uploadNewBtn">
                                        <i class="fas fa-upload"></i> Upload New
                                    </button>
                                </div>
                            </div>
                            <!-- Right Content -->
                            <div class="col-12 col-md-9">
                                <div class="row" id="fileGrid">
                                    <!-- Images will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Add modal to body if it doesn't exist
    if (!document.getElementById('fileManagerModal')) {
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }

    const modal = new bootstrap.Modal(document.getElementById('fileManagerModal'));
    const fileGrid = document.getElementById('fileGrid');
    const targetInput = document.querySelector(selector);

    // Sample images (replace with your actual images)
    const images = [
        { src: '/dashboard/images/movie-thumb/01.webp', folder: 'movie-thumb' },
        { src: '/dashboard/images/movie-thumb/02.webp', folder: 'movie-thumb' },
        { src: '/dashboard/images/movie-thumb/03.webp', folder: 'movie-thumb' },
        { src: '/dashboard/images/movie-thumb/04.webp', folder: 'movie-thumb' },
        { src: '/dashboard/images/movie-thumb/05.webp', folder: 'movie-thumb' },
        { src: '/dashboard/images/movie-thumb/06.webp', folder: 'movie-thumb' },
        { src: '/dashboard/images/show-thumb/01.webp', folder: 'show-thumb' },
        { src: '/dashboard/images/show-thumb/02.webp', folder: 'show-thumb' },
        { src: '/dashboard/images/show-thumb/03.webp', folder: 'show-thumb' }
    ];

    function loadImages(folder = 'movie-thumb') {
        fileGrid.innerHTML = '';
        const filteredImages = images.filter(img => img.folder === folder);
        
        filteredImages.forEach(img => {
            const imageCard = `
                <div class="col-6 col-sm-4 col-lg-3 mb-4">
                    <div class="card h-100">
                        <img src="${img.src}" class="card-img-top" alt="thumbnail" style="height: 150px; object-fit: cover; cursor: pointer;">
                        <div class="card-footer p-2 text-center">
                            <small class="text-muted">${img.src.split('/').pop()}</small>
                        </div>
                    </div>
                </div>
            `;
            fileGrid.insertAdjacentHTML('beforeend', imageCard);
        });

        // Add click handlers to images
        fileGrid.querySelectorAll('img').forEach(img => {
            img.addEventListener('click', function() {
                const selectedSrc = this.src;
                updateSelectedImage(selectedSrc);
                modal.hide();
            });
        });
    }

    // Handle folder switching
    document.querySelectorAll('[data-folder]').forEach(folderLink => {
        folderLink.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('[data-folder]').forEach(link => link.classList.remove('active'));
            this.classList.add('active');
            loadImages(this.dataset.folder);
        });
    });

    // Handle file upload
    document.getElementById('uploadNewBtn').addEventListener('click', function() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.onchange = function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(evt) {
                    updateSelectedImage(evt.target.result);
                    modal.hide();
                };
                reader.readAsDataURL(file);
            }
        };
        input.click();
    });

    function updateSelectedImage(src) {
        // Update the target elements based on the variant
        const container = targetInput.closest('.streamit-upload');
        const isDefaultVariant = container.classList.contains('media-attachment-video');
        
        if (isDefaultVariant) {
            const preview = container.querySelector('.tvshow_genre_thumbnail_previews');
            if (preview) {
                preview.src = src;
                preview.style.display = 'block';
            }
        } else {
            const preview = container.querySelector('.tvshow_genre_thumbnail_preview');
            const trigger = container.querySelector('.streamit_upload_video_button');
            const removeButton = container.querySelector('.streamit_remove_video_button');
            
            if (preview) {
                preview.src = src;
                preview.style.display = 'block';
            }
            
            if (removeButton) {
                removeButton.style.display = 'block';
            }
            
            // Hide default content
            const defaultIcon = trigger.querySelector('.img-icon');
            const defaultText = trigger.querySelector('span');
            if (defaultIcon) defaultIcon.style.display = 'none';
            if (defaultText) defaultText.style.display = 'none';
        }
    }

    // Initialize with movie thumbnails
    loadImages();
    
    // Show the modal
    modal.show();
}