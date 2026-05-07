<?php
/**
 * View Questionnaire Modal/Form
 * Used for displaying questionnaire details
 */
?>

<div class="modal fade" id="viewQuestionnaireModal" tabindex="-1" role="dialog" aria-labelledby="viewQuestionnaireLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewQuestionnaireLabel">View Questionnaire</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="questionnaire-details">
                    <!-- Questionnaire details will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
<?php if (isset($questionnaire)): ?>
    document.getElementById('questionnaire-details').innerHTML = `
        <p><strong>ID:</strong> <?php echo $questionnaire->id ?? 'N/A'; ?></p>
        <p><strong>Name:</strong> <?php echo $questionnaire->name ?? 'N/A'; ?></p>
        <p><strong>Description:</strong> <?php echo $questionnaire->description ?? 'N/A'; ?></p>
        <p><strong>Created At:</strong> <?php echo $questionnaire->created_at ?? 'N/A'; ?></p>
    `;
<?php endif; ?>
</script>
