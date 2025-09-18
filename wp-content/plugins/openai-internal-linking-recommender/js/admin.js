jQuery(document).ready(function($) {
    // Check if we're on the post editor
    const isPostEditor = $('#post').length > 0;
    
    if (isPostEditor) {
        // Get current post ID
        const postId = $('#post_ID').val();
        
        // Generate first recommendation automatically
        generateRecommendations(postId);
    } else {
        // Original tools page functionality
        const $postSelector = $('#post-selector');
        const $linkMode = $('#link-mode');
        const $generateButton = $('#generate-links');
        
        $generateButton.on('click', function() {
            const postId = $postSelector.val();
            const mode = $linkMode.val();
            
            if (!postId) {
                alert('Please select a post first');
                return;
            }
            
            generateRecommendations(postId, mode);
        });
    }
    
    function generateRecommendations(postId) {
        const $loading = $('#loading');
        const $results = $('#results');
        const $recommendationList = $('#recommendation-list');
        
        // Create status bar if it doesn't exist
        if (!$('#internal-links-status').length) {
            const statusBar = `
                <div id="internal-links-status" class="internal-links-status">
                    <div class="status-header">
                        <span class="status-title">Internal Links Status</span>
                        <span class="status-count">0/0</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 0%"></div>
                    </div>
                    <div class="status-message">Calculating recommended links...</div>
                </div>
            `;
            $results.before(statusBar);
        }
        
        // Show loading state
        $loading.show();
        $results.hide();
        $recommendationList.empty();
        
        // Make AJAX request
        $.ajax({
            url: internalLinksAi.ajaxUrl,
            type: 'POST',
            data: {
                action: 'generate_internal_links',
                nonce: internalLinksAi.nonce,
                post_id: postId
            },
            success: function(response) {
                $loading.hide();
                $results.show();
                
                if (response.success) {
                    const recommendations = response.data.recommendations;
                    const linkStats = response.data.link_stats;
                    
                    // Update status bar
                    const $statusBar = $('#internal-links-status');
                    const $statusCount = $statusBar.find('.status-count');
                    const $progressFill = $statusBar.find('.progress-fill');
                    const $statusMessage = $statusBar.find('.status-message');
                    
                    const percentage = Math.min(100, (linkStats.current / linkStats.recommended) * 100);
                    
                    $statusCount.text(`${linkStats.current}/${linkStats.recommended}`);
                    $progressFill.css('width', `${percentage}%`);
                    
                    if (linkStats.is_complete) {
                        $progressFill.addClass('complete');
                        $statusMessage.addClass('complete').text('Great! You have enough internal links.');
                    } else {
                        $progressFill.removeClass('complete');
                        $statusMessage.removeClass('complete')
                            .text(`Add ${linkStats.recommended - linkStats.current} more internal links for optimal SEO.`);
                    }
                    
                    if (recommendations.length === 0) {
                        $recommendationList.append('<li>No new recommendations found. All relevant posts are already linked in this article.</li>');
                        return;
                    }
                    
                    // Store all recommendations in a queue
                    const recommendationQueue = [...recommendations];
                    
                    // Function to show next recommendation
                    function showNextRecommendation() {
                        if (recommendationQueue.length === 0) {
                            // Show loading indicator
                            const loadingItem = $('<li>')
                                .addClass('loading-indicator')
                                .html('<div class="spinner"></div> Generating new recommendations...');
                            $recommendationList.append(loadingItem);
                            
                            // Make a new AJAX call to get more recommendations
                            $.ajax({
                                url: internalLinksAi.ajaxUrl,
                                type: 'POST',
                                data: {
                                    action: 'generate_internal_links',
                                    nonce: internalLinksAi.nonce,
                                    post_id: postId
                                },
                                success: function(response) {
                                    // Remove loading indicator
                                    $('.loading-indicator').remove();
                                    
                                    if (response.success && response.data.length > 0) {
                                        // Add new recommendations to the queue
                                        recommendationQueue.push(...response.data);
                                        // Show the first new recommendation
                                        showNextRecommendation();
                                    } else {
                                        $recommendationList.append('<li>No more recommendations available</li>');
                                    }
                                },
                                error: function(xhr, status, error) {
                                    // Remove loading indicator
                                    $('.loading-indicator').remove();
                                    $recommendationList.append('<li>Error: ' + error + '</li>');
                                }
                            });
                            return;
                        }
                        
                        const rec = recommendationQueue.shift();
                        const listItem = $('<li>').addClass('recommendation-item');
                        
                        // Title and score
                        const header = $('<div>').addClass('recommendation-header');
                        const link = $('<a>')
                            .attr('href', rec.url)
                            .attr('target', '_blank')
                            .text(rec.title);
                        
                        const score = $('<span>')
                            .addClass('score')
                            .text(rec.score);
                        
                        header.append(link, score);
                        
                        // Change description
                        // const changeDescription = $('<div>');
                        //     .addClass('change-description');
                        //     .text(rec.change_description);
                        
                        // Context
                        const context = $('<div>').addClass('context');
                        
                        // Add change preview
                        const changePreview = $('<div>').addClass('change-preview');
                        
                        if (rec.change_type === 'replace') {
                            // Show "Before" and "After" for replacements
                            const beforeAfter = $('<div>').addClass('before-after');
                            
                            const before = $('<div>').addClass('before')
                                .append(
                                    $('<div>').addClass('label').text('Current paragraph:'),
                                    $('<div>').addClass('content').text(rec.target_paragraph_preview)
                                );
                            
                            const after = $('<div>').addClass('after')
                                .append(
                                    $('<div>').addClass('label').text('New paragraph:'),
                                    $('<div>').addClass('content').html(rec.html_snippet)
                                );
                            
                            beforeAfter.append(before, after);
                            changePreview.append(beforeAfter);
                        } else {
                            // Show insertion point for new paragraphs (just show new paragraph)
                            const newContent = $('<div>').addClass('new-content')
                                .append(
                                    $('<div>').addClass('label').text('New paragraph:'),
                                    $('<div>').addClass('content').html(rec.html_snippet)
                                );
                            changePreview.append(newContent);
                        }
                        
                        context.append(changePreview);
                        
                        // Action buttons
                        const actions = $('<div>').addClass('recommendation-actions');
                        const acceptButton = $('<button>')
                            .addClass('button button-primary accept-recommendation')
                            .text('Accept')
                            .data('snippet', rec.html_snippet)
                            .data('paragraph-index', rec.target_paragraph_index)
                            .data('should-replace', rec.should_replace);
                        
                        const declineButton = $('<button>')
                            .addClass('button button-secondary decline-recommendation')
                            .text('Decline');
                        
                        actions.append(acceptButton, declineButton);
                        
                        // Assemble the recommendation
                        listItem.append(header, context, actions);
                        $recommendationList.append(listItem);
                        
                        // Handle accept/decline actions
                        $('.accept-recommendation').off('click').on('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            const $button = $(this);
                            const snippet = $button.data('snippet');
                            const paragraphIndex = $button.data('paragraph-index');
                            const shouldReplace = $button.data('should-replace');
                            
                            // Get the editor instance
                            const editor = tinyMCE.get('content');
                            let content;
                            
                            if (editor && !editor.isHidden()) {
                                content = editor.getContent();
                            } else {
                                content = $('#content').val();
                            }
                            
                            const paragraphs = content.split('\n');
                            
                            if (shouldReplace && paragraphIndex >= 0 && paragraphIndex < paragraphs.length) {
                                // Replace the target paragraph
                                paragraphs[paragraphIndex] = snippet;
                            } else {
                                // Insert at the target paragraph
                                paragraphs.splice(paragraphIndex, 0, snippet);
                            }
                            
                            // Update content in both visual and text editors
                            const newContent = paragraphs.join('\n');
                            if (editor && !editor.isHidden()) {
                                // Prevent editor from marking content as changed
                                editor.undoManager.transact(function() {
                                    editor.setContent(newContent);
                                });
                                editor.isNotDirty = true;
                                editor.nodeChanged();
                                
                                // Scroll to the modified content in the visual editor
                                const modifiedParagraph = editor.dom.select('p')[paragraphIndex];
                                if (modifiedParagraph) {
                                    editor.selection.select(modifiedParagraph);
                                    editor.selection.scrollIntoView(modifiedParagraph);
                                }
                            } else {
                                $('#content').val(newContent);
                                
                                // Scroll to the modified content in the text editor
                                const textarea = document.getElementById('content');
                                const lines = textarea.value.split('\n');
                                let position = 0;
                                
                                // Calculate the position of the modified paragraph
                                for (let i = 0; i < paragraphIndex; i++) {
                                    position += lines[i].length + 1; // +1 for the newline character
                                }
                                
                                // Set cursor position and scroll
                                textarea.focus();
                                textarea.setSelectionRange(position, position);
                                textarea.scrollTop = textarea.scrollHeight * (paragraphIndex / lines.length);
                            }
                            
                            // Update status after accepting
                            const currentCount = parseInt($statusCount.text().split('/')[0]);
                            $statusCount.text(`${currentCount + 1}/${linkStats.recommended}`);
                            
                            const newPercentage = Math.min(100, ((currentCount + 1) / linkStats.recommended) * 100);
                            $progressFill.css('width', `${newPercentage}%`);
                            
                            if (currentCount + 1 >= linkStats.recommended) {
                                $progressFill.addClass('complete');
                                $statusMessage.addClass('complete').text('Great! You have enough internal links.');
                            }
                            
                            // Remove the recommendation
                            $button.closest('.recommendation-item').fadeOut(function() {
                                $(this).remove();
                                // Show next recommendation
                                showNextRecommendation();
                            });
                        });
                        
                        $('.decline-recommendation').off('click').on('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            const $button = $(this);
                            
                            // Remove the recommendation
                            $button.closest('.recommendation-item').fadeOut(function() {
                                $(this).remove();
                                // Show next recommendation
                                showNextRecommendation();
                            });
                        });
                    }
                    
                    // Show first recommendation
                    showNextRecommendation();
                } else {
                    $recommendationList.append('<li>Error: ' + response.data + '</li>');
                }
            },
            error: function(xhr, status, error) {
                $loading.hide();
                $results.show();
                $recommendationList.append('<li>Error: ' + error + '</li>');
            }
        });
    }
}); 
