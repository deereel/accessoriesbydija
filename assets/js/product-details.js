document.addEventListener('DOMContentLoaded', function() {
    const mainElement = document.querySelector('main');
    const productData = JSON.parse(mainElement.dataset.product);
    const productId = productData.id;
    const basePrice = parseFloat(productData.price);
    const productImages = JSON.parse(mainElement.dataset.images);

    let selectedMaterial = null;
    let selectedVariation = null;
    let selectedSize = null;
    let selectedMaterialName = '';
    let selectedVariationData = {};
    let selectedSizeData = {};
    let maxStock = 0;

    // Mouse tracking magnification
    const mainImageContainer = document.getElementById('mainImage');
    if (mainImageContainer) {
        mainImageContainer.addEventListener('mouseenter', function() {
            this.style.cursor = 'zoom-in';
        });
        
        mainImageContainer.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;
            
            const img = this.querySelector('img');
            if (img) {
                img.style.transform = 'scale(2)';
                img.style.transformOrigin = `${x}% ${y}%`;
            } else {
                this.style.transform = 'scale(2)';
                this.style.transformOrigin = `${x}% ${y}%`;
            }
        });
        
        mainImageContainer.addEventListener('mouseleave', function() {
            const img = this.querySelector('img');
            if (img) {
                img.style.transform = 'scale(1)';
            } else {
                this.style.transform = 'scale(1)';
            }
        });
    }

    function changeImage(thumbnail) {
        document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
        thumbnail.classList.add('active');
        const mainImage = document.getElementById('mainImage');
        const img = thumbnail.querySelector('img');
        if (img) {
            mainImage.innerHTML = `<img src="${img.src}" alt="${productData.name} - Premium Jewelry by Accessories By Dija" style="width:100%;height:100%;object-fit:cover;">`;
        } else {
            mainImage.innerHTML = thumbnail.innerHTML;
        }
    }

    function selectMaterial(materialId) {
        console.log('selectMaterial function called with materialId:', materialId);
        selectedMaterial = materialId;
        selectedVariation = null;
        selectedSize = null;

        const materialBtn = document.querySelector(`[data-material-id="${materialId}"]`);
        if (materialBtn) {
            selectedMaterialName = materialBtn.textContent;
        }

        // Hide material guidance, show variation guidance
        const materialGuidance = document.getElementById('materialGuidance');
        const variationGuidance = document.getElementById('variationGuidance');
        if (materialGuidance) {
            materialGuidance.classList.remove('visible');
            materialGuidance.classList.add('hidden');
        }
        if (variationGuidance) {
            variationGuidance.classList.remove('hidden');
            variationGuidance.classList.add('visible');
        }
        
        document.querySelectorAll('#materialOptions .option-btn').forEach(btn => {
            btn.classList.remove('selected');
            if (btn.dataset.materialId == materialId) {
                btn.classList.add('selected');
            }
        });

        fetch(`get_variations.php?product_id=${productId}&material_id=${materialId}`)
            .then(response => response.json())
            .then(variations => {
                console.log('Variations received:', variations);
                const variationGroup = document.getElementById('variationGroup');
                const variationOptions = document.getElementById('variationOptions');

                if (variations.length > 0) {
                    variationOptions.innerHTML = '';
                    variations.forEach(variation => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'option-btn';
                        btn.dataset.action = 'select-variation';
                        btn.dataset.variationData = JSON.stringify(variation);
                        btn.textContent = variation.tag || 'Standard';
                        variationOptions.appendChild(btn);
                    });
                    variationGroup.style.display = 'block';
                } else {
                    variationGroup.style.display = 'none';
                }

                document.getElementById('sizeGroup').style.display = 'none';
                updateComponentSummary();
                updateAddToCartButton();
            });
    }
    
    function selectVariation(variationId, priceAdjustment, variationData) {
        console.log('Selected variation:', variationId, variationData);
        selectedVariation = variationId;
        selectedSize = null;
        selectedVariationData = variationData || {};

        // Update image based on variation tag
        const variationTag = variationData.tag;
        if (variationTag) {
            const image = productImages.find(img => img.tag === variationTag);
            if (image) {
                const mainImage = document.getElementById('mainImage');
                mainImage.innerHTML = `<img src="/${image.image_url}" alt="${productData.name} - ${image.alt_text || 'Premium Jewelry by Accessories By Dija'}" style="width:100%;height:100%;object-fit:cover;">`;

                // Update active thumbnail
                document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
                const newActiveThumbnail = document.querySelector(`.thumbnail[data-image-id="${image.id}"]`);
                if (newActiveThumbnail) {
                    newActiveThumbnail.classList.add('active');
                }
            }
        }

        document.querySelectorAll('#variationOptions .option-btn').forEach(btn => {
            btn.classList.remove('selected');
            const btnData = JSON.parse(btn.dataset.variationData);
            if (btnData.id == variationId) {
                btn.classList.add('selected');
            }
        });

        const stock = variationData.stock_quantity || 0;
        document.getElementById('stockInfo').textContent = `${stock} in stock`;

        // Hide variation guidance, show size guidance
        const variationGuidance = document.getElementById('variationGuidance');
        const sizeGuidance = document.getElementById('sizeGuidance');
        if (variationGuidance) {
            variationGuidance.classList.remove('visible');
            variationGuidance.classList.add('hidden');
        }
        if (sizeGuidance) {
            sizeGuidance.classList.remove('hidden');
            sizeGuidance.classList.add('visible');
        }
        
        const finalPrice = parseFloat(priceAdjustment) || basePrice;

        document.getElementById('finalPrice').innerHTML = `£${finalPrice.toFixed(2)}`;
        document.getElementById('finalPrice').style.display = 'block';
        document.getElementById('basePrice').style.display = 'none';

        console.log('Fetching sizes for variation ID:', variationId);

        fetch(`get_sizes.php?variation_id=${variationId}`)
            .then(response => response.json())
            .then(sizes => {
                console.log('Sizes received:', sizes);
                const sizeGroup = document.getElementById('sizeGroup');
                const sizeOptions = document.getElementById('sizeOptions');

                if (sizes.length > 0) {
                    sizeOptions.innerHTML = '';
                    sizes.forEach(size => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'option-btn';
                        btn.dataset.action = 'select-size';
                        btn.dataset.sizeData = JSON.stringify(size);
                        btn.textContent = size.size;

                        if (size.stock_quantity <= 0) {
                            btn.disabled = true;
                            btn.textContent += ' (Out of Stock)';
                        }

                        sizeOptions.appendChild(btn);
                    });
                    sizeGroup.style.display = 'block';
                } else {
                    sizeGroup.style.display = 'none';
                }
                updateComponentSummary();
                updateAddToCartButton();
            })
            .catch(error => {
                console.error('Error fetching sizes:', error);
            });
    }
    
    function selectSize(sizeId, priceAdjustment, stock, sizeData) {
        console.log('Selected size:', sizeId, sizeData);
        selectedSize = sizeId;
        selectedSizeData = sizeData || {};
        maxStock = stock;

        document.querySelectorAll('#sizeOptions .option-btn').forEach(btn => {
            btn.classList.remove('selected');
            const btnData = JSON.parse(btn.dataset.sizeData);
            if (btnData.id == sizeId) {
                btn.classList.add('selected');
            }
        });

        const finalPrice = parseFloat(priceAdjustment) || (selectedVariationData.price_adjustment > 0 ? parseFloat(selectedVariationData.price_adjustment) : basePrice);
        document.getElementById('finalPrice').innerHTML = `£${finalPrice.toFixed(2)}`;
        document.getElementById('stockInfo').textContent = `${stock} in stock`;

        // Hide size guidance when size is selected
        const sizeGuidance = document.getElementById('sizeGuidance');
        if (sizeGuidance) {
            sizeGuidance.classList.remove('visible');
            sizeGuidance.classList.add('hidden');
        }
        
        const quantityInput = document.getElementById('quantityInput');
        quantityInput.max = stock;
        if (parseInt(quantityInput.value) > stock) {
            quantityInput.value = stock;
        }

        updateComponentSummary();
        updateAddToCartButton();
    }

    function updateComponentSummary() {
        const summary = document.getElementById('componentSummary');
        const content = document.getElementById('summaryContent');

        if (!selectedMaterial || !selectedVariation) {
            summary.style.display = 'none';
            return;
        }

        let summaryHtml = `<strong>Material:</strong> ${selectedMaterialName}<br>`;

        if (selectedVariationData.color) {
            summaryHtml += `<strong>Color:</strong> ${selectedVariationData.color}<br>`;
        }

        if (selectedVariationData.adornment) {
            summaryHtml += `<strong>Adornment:</strong> ${selectedVariationData.adornment}<br>`;
        }

        if (selectedSize && selectedSizeData.size) {
            summaryHtml += `<strong>Size:</strong> ${selectedSizeData.size}<br>`;
        }

        const quantity = document.getElementById('quantityInput')?.value || 1;
        summaryHtml += `<strong>Quantity:</strong> ${quantity}<br>`;

        let finalPrice = basePrice;
        if (selectedSize && selectedSizeData.price_adjustment && selectedSizeData.price_adjustment > 0) {
            finalPrice = parseFloat(selectedSizeData.price_adjustment);
        } else if (selectedVariationData.price_adjustment && selectedVariationData.price_adjustment > 0) {
            finalPrice = parseFloat(selectedVariationData.price_adjustment);
        }
        summaryHtml += `<strong>Price:</strong> £${finalPrice.toFixed(2)}`;

        content.innerHTML = summaryHtml;
        summary.style.display = 'block';
    }

    function changeQuantity(change) {
        const input = document.getElementById('quantityInput');
        const newValue = parseInt(input.value) + change;
        if (newValue >= 1 && newValue <= maxStock) {
            input.value = newValue;
            updateComponentSummary();
        }
    }

    function updateAddToCartButton() {
        const btn = document.getElementById('addToCartBtn');
        const variationGroup = document.getElementById('variationGroup');
        const sizeGroup = document.getElementById('sizeGroup');
        const materialOptions = document.getElementById('materialOptions');

        // If no materials exist (simple product), enable button
        if (materialOptions.children.length === 0) {
            btn.disabled = false;
            btn.textContent = 'Add to Cart';
            document.getElementById('quantitySelector').style.display = 'block';
            return;
        }

        // For products with variations
        if (selectedMaterial && (variationGroup.style.display === 'none' || selectedVariation) && (sizeGroup.style.display === 'none' || selectedSize)) {
            btn.disabled = false;
            btn.textContent = 'Add to Cart';
            document.getElementById('quantitySelector').style.display = 'block';
        } else {
            btn.disabled = true;
            document.getElementById('quantitySelector').style.display = 'none';
            if (!selectedVariation) {
                document.getElementById('componentSummary').style.display = 'none';
            }

            // Determine the next step message
            if (!selectedMaterial) {
                btn.textContent = 'Select Your Preferred Material to Proceed';
            } else if (variationGroup.style.display !== 'none' && !selectedVariation) {
                btn.textContent = 'Select Your Preferred Variation';
            } else if (sizeGroup.style.display !== 'none' && !selectedSize) {
                btn.textContent = 'Select Preferred Size';
            } else {
                btn.textContent = 'Select Your Preferred Material to Proceed';
            }
        }
    }

    function addToCartFromProduct() {
        if (!window.cartHandler) {
            console.error('Cart handler not available.');
            alert('Could not add to cart. Please refresh the page.');
            return;
        }

        const quantity = document.getElementById('quantityInput').value;

        let price = basePrice;
        if (selectedSize && selectedSizeData.price_adjustment > 0) {
            price = selectedSizeData.price_adjustment;
        } else if (selectedVariationData.price_adjustment > 0) {
            price = selectedVariationData.price_adjustment;
        }

        const productData = {
            product_id: productId,
            quantity: parseInt(quantity),
            material_id: selectedMaterial,
            variation_id: selectedVariation,
            size_id: selectedSize,
            selected_price: price,
            variation_name: selectedVariationData.tag || '',
            image: document.getElementById('mainImage').querySelector('img')?.src || ''
        };
        console.log('Constructed productData:', productData);
        console.log('productData.material_id:', productData.material_id);

        console.log('Final selections - Material:', selectedMaterial, 'Variation:', selectedVariation, 'Size:', selectedSize);
        console.log('Sending productData to cart:', productData);
        window.cartHandler.addToCart(productData);
    }

    function toggleWishlist(productId, btn) {
        // Check if user is logged in
        fetch('/api/wishlist.php?product_id=' + productId)
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                    return;
                }

                const isInWishlist = data.in_wishlist;
                const method = isInWishlist ? 'DELETE' : 'POST';
                const url = isInWishlist ? '/api/wishlist.php?product_id=' + productId : '/api/wishlist.php';

                fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: method === 'POST' ? JSON.stringify({ product_id: productId }) : null
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        if (isInWishlist) {
                            btn.querySelector('i').className = 'far fa-heart';
                            btn.classList.remove('active');
                        } else {
                            btn.querySelector('i').className = 'fas fa-heart';
                            btn.classList.add('active');
                        }
                    } else {
                        alert('Error: ' + result.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            })
            .catch(error => {
                console.error('Error checking wishlist:', error);
                alert('An error occurred. Please try again.');
            });
    }

    function toggleReviewForm() {
        const formContainer = document.getElementById('reviewFormContainer');
        const btn = document.getElementById('writeReviewBtn');

        if (formContainer.style.display === 'none') {
            formContainer.style.display = 'block';
            btn.textContent = 'Cancel Review';
        } else {
            formContainer.style.display = 'none';
            btn.textContent = 'Write a Review';
        }
    }

    function toggleAccordion(header) {
        const item = header.parentElement;
        const content = item.querySelector('.accordion-content');
        const icon = header.querySelector('i');

        // Close all other accordions
        document.querySelectorAll('.accordion-item').forEach(otherItem => {
            if (otherItem !== item) {
                otherItem.querySelector('.accordion-content').style.maxHeight = '0';
                otherItem.querySelector('.accordion-header').classList.remove('active');
                otherItem.querySelector('.accordion-header i').classList.remove('fa-chevron-up');
                otherItem.querySelector('.accordion-header i').classList.add('fa-chevron-down');
            }
        });

        // Toggle current accordion
        if (content.style.maxHeight === '0px' || !content.style.maxHeight) {
            content.style.maxHeight = content.scrollHeight + 'px';
            header.classList.add('active');
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
        } else {
            content.style.maxHeight = '0';
            header.classList.remove('active');
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        }
    }

    function submitReview(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');

        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';

        fetch('/api/submit-review.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Thank you for your review! It will be published after approval.');
                form.reset();
                toggleReviewForm();
            } else {
                alert('Error submitting review: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while submitting your review.');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Review';
        });

        return false;
    }

    // Initialize wishlist button state
    const wishlistBtn = document.querySelector('[data-action="toggle-wishlist"]');
    if (wishlistBtn) {
        const productId = wishlistBtn.dataset.productId;
        fetch('/api/wishlist.php?product_id=' + productId)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.in_wishlist) {
                    wishlistBtn.querySelector('i').className = 'fas fa-heart';
                    wishlistBtn.classList.add('active');
                }
            })
            .catch(error => console.error('Error checking wishlist:', error));
    }

    // Event delegation for all click actions
    document.addEventListener('click', function(e) {
        if (e.target.matches('[data-action="select-material"]')) {
            const materialId = e.target.dataset.materialId;
            selectMaterial(materialId);
        } else if (e.target.matches('[data-action="select-variation"]')) {
            const variationData = JSON.parse(e.target.dataset.variationData);
            selectVariation(variationData.id, variationData.price_adjustment, variationData);
        } else if (e.target.matches('[data-action="select-size"]')) {
            const sizeData = JSON.parse(e.target.dataset.sizeData);
            selectSize(sizeData.id, sizeData.price_adjustment, sizeData.stock_quantity, sizeData);
        } else if (e.target.matches('[data-action="change-quantity"]')) {
            const change = parseInt(e.target.dataset.change);
            changeQuantity(change);
        } else if (e.target.matches('[data-action="add-to-cart"]')) {
            addToCartFromProduct();
        } else if (e.target.matches('[data-action="toggle-wishlist"]')) {
            const productId = e.target.dataset.productId;
            toggleWishlist(productId, e.target);
        } else if (e.target.matches('[data-action="toggle-accordion"]')) {
            toggleAccordion(e.target);
        } else if (e.target.matches('[data-action="toggle-review-form"]')) {
            toggleReviewForm();
        } else if (e.target.closest('[data-action="change-image"]')) {
            const thumbnail = e.target.closest('[data-action="change-image"]');
            changeImage(thumbnail);
        }
    });
});
