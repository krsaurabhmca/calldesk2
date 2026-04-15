<?php
// includes/custom_fields_helper.php

function getCustomFields($conn, $org_id) {
    $res = mysqli_query($conn, "SELECT * FROM lead_custom_fields WHERE organization_id = $org_id ORDER BY id ASC");
    $fields = [];
    while($row = mysqli_fetch_assoc($res)) {
        $fields[] = $row;
    }
    return $fields;
}

function getLeadCustomData($conn, $lead_id) {
    $res = mysqli_query($conn, "SELECT d.*, f.field_name FROM lead_custom_data d JOIN lead_custom_fields f ON d.field_id = f.id WHERE d.lead_id = $lead_id");
    $data = [];
    while($row = mysqli_fetch_assoc($res)) {
        $data[$row['field_id']] = $row['field_value'];
    }
    return $data;
}

function renderCustomField($field, $current_value = '') {
    $required = $field['is_required'] ? 'required' : '';
    $html = '<div class="form-group">';
    $html .= '<label class="form-label" style="font-size: 0.7rem; margin-bottom: 0.25rem;">' . htmlspecialchars($field['field_label']);
    if ($field['is_required']) $html .= ' <span style="color: var(--danger);">*</span>';
    $html .= '</label>';

    if ($field['field_type'] === 'select') {
        $html .= '<select name="custom[' . $field['id'] . ']" class="form-control" style="height: 38px; font-size: 0.85rem;" ' . $required . '>';
        $html .= '<option value="">-- Select --</option>';
        $options = explode(',', $field['field_options']);
        foreach ($options as $opt) {
            $opt = trim($opt);
            $selected = ($opt == $current_value) ? 'selected' : '';
            $html .= '<option value="' . htmlspecialchars($opt) . '" ' . $selected . '>' . htmlspecialchars($opt) . '</option>';
        }
        $html .= '</select>';
    } elseif ($field['field_type'] === 'date') {
        $html .= '<input type="date" name="custom[' . $field['id'] . ']" class="form-control" style="height: 38px; font-size: 0.85rem;" value="' . htmlspecialchars($current_value) . '" ' . $required . '>';
    } elseif ($field['field_type'] === 'number') {
        $html .= '<input type="number" name="custom[' . $field['id'] . ']" class="form-control" style="height: 38px; font-size: 0.85rem;" value="' . htmlspecialchars($current_value) . '" ' . $required . '>';
    } else {
        $html .= '<input type="text" name="custom[' . $field['id'] . ']" class="form-control" style="height: 38px; font-size: 0.85rem;" value="' . htmlspecialchars($current_value) . '" ' . $required . '>';
    }

    $html .= '</div>';
    return $html;
}
?>
