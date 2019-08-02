Given(/^I have recently edited pages on my watchlist$/) do
  api.create_page 'Selenium Watchlist', 'Edit by #{user}'
  api.action('watch', token_type: 'watch', titles: 'Selenium Watchlist')
end

When(/^the Pages tab is selected$/) do
  expect(on(WatchlistPage).selected_pages_tab_element.when_present).to be_visible
end

When(/^I click the Pages tab$/) do
  on(WatchlistPage).pages_tab_link_element.when_present.click
end

When(/^I switch to the list view of the watchlist$/) do
  on(WatchlistPage).list_link_element.click
end

When(/^I switch to the modified view of the watchlist$/) do
  on(WatchlistPage).feed_link_element.click
end

Then(/^I should see a list of diff summary links$/) do
  expect(on(WatchlistPage).page_list_diffs_element.when_present).to be_visible
end

Then(/^I should see a list of pages I am watching$/) do
  expect(on(WatchlistPage).page_list_a_to_z_element.when_present).to be_visible
end

Then(/^the a to z button should be selected$/) do
  expect(on(WatchlistPage).list_link_element.parent.element.class_name).to match 'is-on'
end

Then(/^the modified button should be selected$/) do
  expect(on(WatchlistPage).feed_link_element.parent.element.class_name).to match 'is-on'
end

Then(/^I am informed on how to add pages to my watchlist$/) do
  expect(on(WatchlistPage).empty_howto_element.when_present).to be_visible
end

Then(/^I am told there are no new changes$/) do
  expect(on(WatchlistPage).empty_panel_element.when_present).to be_visible
  expect(on(WatchlistPage).empty_howto_element).not_to be_visible
end
