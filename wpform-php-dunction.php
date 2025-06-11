function wpforms_show_entry_by_id_shortcode() {
    global $wpdb;
    ob_start();

    $current_user_id = get_current_user_id();
    $selected_entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : null;
    $patient_id = isset($_POST['patient_id']) ? sanitize_text_field($_POST['patient_id']) : null;

    $entries = [];

    // If a specific entry is selected (by entry ID or patient ID), show that
    if ( $selected_entry_id || $patient_id ) {
        if ( $selected_entry_id ) {
            $entry = wpforms()->entry->get( $selected_entry_id, [ 'cap' => false, 'fields' => true ] );
            if ( $entry ) {
                $entries[] = $entry;
            }
        }

        if ( $patient_id ) {
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}wpforms_entries WHERE fields LIKE %s",
                    '%' . $wpdb->esc_like($patient_id) . '%'
                )
            );

            if ( !empty($results) ) {
                foreach ( $results as $result ) {
                    $entry = wpforms()->entry->get( $result->entry_id, [ 'cap' => false, 'fields' => true ] );
                    if ( $entry ) {
                        $entries[] = $entry;
                    }
                }
            }
        }
    }

    // 🟩 Show table for selected entry if found
    if ( !empty($entries) ) {
		
											
			// 🔙 Back button to return to search/list
echo "<form method='post'>
        <button type='submit' style='border: none; border-radius: 25px;fcursor: pointer;'>
            بازگشت
        </button>
      </form>";
		
		
        foreach ( $entries as $entry ) {
            $form = wpforms()->form->get( $entry->form_id );
            $form_name = $form->post_title ?? 'فرم بدون نام';
            $form_data = wpforms_decode( $form->post_content );
            $entry_fields = json_decode( $entry->fields, true );

            echo "<h3>🆔 شناسه ورودی: {$entry->entry_id}</h3>";
            echo "<h3>📝 اسم فرم: {$form_name}</h3>";

            // Show session number
            $session_number = '';
            foreach ( $form_data['fields'] as $fid => $field ) {
                if ( $field['type'] === 'select' && $field['label'] === 'شماره جلسه' ) {
                    $session_number = $entry_fields[$fid]['value'] ?? '';
                    break;
                }
            }

            if ( !empty($session_number) ) {
                echo "<h3>📅 {$session_number}</h3>";
            }

			
			
            echo "<table style='border-collapse: collapse; width: 100%; direction: rtl;'>";
            echo "<thead>
                    <tr>
                        <th style='border: 1px solid #ccc; padding: 8px;'>عنوان</th>
                        <th style='border: 1px solid #ccc; padding: 8px;'>مقدار</th>
                    </tr>
                  </thead><tbody>";

            $fields = $form_data['fields'];
            $field_ids = array_keys($fields);
            $total_fields = count($field_ids);

            for ( $i = 0; $i < $total_fields; $i++ ) {
                $fid = $field_ids[$i];
                $field = $fields[$fid];
                $type = $field['type'];
                $label = $field['label'] ?? '';

                // Handle divider
                if ( $type === 'divider' && !empty($label) ) {
                    $has_content_after = false;
                    for ( $j = $i + 1; $j < $total_fields; $j++ ) {
                        $next_fid = $field_ids[$j];
                        $next_field = $fields[$next_fid];
                        if ( $next_field['type'] === 'divider' ) break;

                        if (
                            isset($entry_fields[$next_fid]) &&
                            !empty($entry_fields[$next_fid]['value'])
                        ) {
                            $has_content_after = true;
                            break;
                        }
                    }
                    if ( $has_content_after ) {
                        echo "<tr>
                                <td colspan='2' style='background: #f0f0f0; font-weight: bold; padding: 10px; text-align: right; border: 1px solid #ccc;'>
                                    " . esc_html($label) . "
                                </td>
                              </tr>";
                    }
                    continue;
                }

                // Show field if it has value
                if ( isset($entry_fields[$fid]) && !empty($entry_fields[$fid]['value']) ) {
                    echo "<tr>
                            <td style='border: 1px solid #ccc; padding: 8px;'>" . esc_html($label) . "</td>
                            <td style='border: 1px solid #ccc; padding: 8px;'>" . esc_html($entry_fields[$fid]['value']) . "</td>
                          </tr>";
                }
            }

            echo "</tbody></table><hr style='margin: 40px 0;'>";
        }
    }

    // 🟦 Show list of current user’s entries
    if ( is_user_logged_in() && !$selected_entry_id && !$patient_id ) {
        $user_entries = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT entry_id, form_id, date FROM {$wpdb->prefix}wpforms_entries WHERE user_id = %d ORDER BY date DESC",
                $current_user_id
            )
        );

        if ( !empty($user_entries) ) {
            echo "<h3>📋 لیست فرم‌های ثبت‌شده توسط شما:</h3><ul style='list-style: none; padding: 0;'>";

            foreach ( $user_entries as $entry ) {
                $form = wpforms()->form->get( $entry->form_id );
                $form_name = $form->post_title ?? 'فرم بدون نام';
                $entry_date = date_i18n( 'Y/m/d H:i', strtotime($entry->date) );

                echo "<li style='margin-bottom: 10px;'>
                        <form method='post' style='display:inline-block;'>
                            <input type='hidden' name='entry_id' value='{$entry->entry_id}' />
 

		                            <button type='submit' style='background: #fff;color: currentcolor;margin: 0;'>
                               {$entry->entry_id} - {$form_name} <small>({$entry_date})</small> 
                            </button>
							
                        </form>
                      </li>";
            }

            echo "</ul><hr>";
        } else {
            echo "<p>هنوز هیچ فرم ثبت نکرده‌اید.</p>";
        }
    }

    ?>

    <form method="post" style="margin-top: 2em; padding: 30px; border-radius: 20px; background: #f9f9f9; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
        <h4 style="color: #444;">جستجوی دستی با شناسه</h4>

        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <div style="flex: 1;">
                <label for="entry_id">شناسه ورودی:</label>
                <input type="text" name="entry_id" id="entry_id" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 25px;" />
            </div>
            <div style="flex: 1;">
                <label for="patient_id">شناسه بیمار:</label>
                <input type="text" name="patient_id" id="patient_id" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 25px;" />
            </div>
        </div>

        <div style="margin-top: 20px; text-align: left;">
            <button type="submit" style="padding: 10px 20px; border: none; border-radius: 25px; background: #20bec6; color: white; font-size: 16px; cursor: pointer;">جستجو</button>
        </div>
    </form>

    <?php

    return ob_get_clean();
}

add_shortcode( 'wpforms_entry_lookup', 'wpforms_show_entry_by_id_shortcode' );
