import { __, sprintf } from "@wordpress/i18n";
import {
  InspectorControls,
  useBlockProps,
  InnerBlocks,
  RichText,
  AlignmentToolbar,
  BlockControls,
  FontSizePicker,
  useSettings,
} from "@wordpress/block-editor";
import { Button, PanelBody, SelectControl } from "@wordpress/components";
import { useSelect } from "@wordpress/data";
import { useEffect, useState } from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
import "./editor.scss";
import React from "react";

const ALLOWED_BLOCKS = ["core/heading", "core/paragraph"];
const TEMPLATE = [["core/heading", { level: 2 }]];

// Default number of items to show when no filters are applied
const DEFAULT_PER_PAGE = 15;
const getFontSizeSlug = (fontSize) =>
  fontSize?.startsWith("var:preset|font-size|")
    ? fontSize.split("|").pop()
    : fontSize;

const getTermOptions = (terms, allLabel) => [
  { label: allLabel, value: "" },
  ...(terms || []).map((term) => ({
    label: term.name,
    value: term.slug,
  })),
];

const getSelectedTermName = (terms, slug, fallback) => {
  if (!slug) {
    return fallback;
  }

  const term = (terms || []).find((item) => item.slug === slug);

  return term ? term.name : slug;
};

const getSelectedTermId = (terms, slug) => {
  if (!terms || !slug) return null;
  const term = terms.find((item) => item.slug === slug);
  return term ? term.id : null;
};

const getPlainText = (html = "") => html.replace(/<[^>]*>/g, "");

const groupServices = (
  services,
  applianceTerm,
  workTerm,
  applianceTerms,
  workTerms,
) => {
  if (!services || services.length === 0) {
    return [];
  }

  // If both filters are selected, no grouping
  if (applianceTerm && workTerm) {
    return [{ items: services }];
  }

  // Helper to get term names from service
  const getTermNames = (service, taxonomyKey) => {
    if (service[taxonomyKey]) {
      return service[taxonomyKey].map((term) =>
        typeof term === "object" ? term.name : term,
      );
    }
    return ["Uncategorized"];
  };

  // If only appliance type is selected, group by work type
  if (applianceTerm && !workTerm) {
    const groups = {};
    services.forEach((service) => {
      const workTypes = getTermNames(service, "service_work_type");
      const groupKey = workTypes[0] || "Uncategorized";
      if (!groups[groupKey]) {
        groups[groupKey] = [];
      }
      groups[groupKey].push(service);
    });
    return Object.entries(groups).map(([key, items]) => ({
      title: key,
      items,
    }));
  }

  // If only work type is selected, group by appliance type
  if (!applianceTerm && workTerm) {
    const groups = {};
    services.forEach((service) => {
      const applianceTypes = getTermNames(service, "service_appliance_type");
      const groupKey = applianceTypes[0] || "Uncategorized";
      if (!groups[groupKey]) {
        groups[groupKey] = [];
      }
      groups[groupKey].push(service);
    });
    return Object.entries(groups).map(([key, items]) => ({
      title: key,
      items,
    }));
  }

  // If no filters, group by both (two-level grouping)
  const primaryGroups = {};
  services.forEach((service) => {
    const applianceTypes = getTermNames(service, "service_appliance_type");
    const workTypes = getTermNames(service, "service_work_type");

    applianceTypes.forEach((appType) => {
      if (!primaryGroups[appType]) {
        primaryGroups[appType] = {};
      }
      workTypes.forEach((workType) => {
        if (!primaryGroups[appType][workType]) {
          primaryGroups[appType][workType] = [];
        }
        primaryGroups[appType][workType].push(service);
      });
    });
  });

  return Object.entries(primaryGroups).map(([appliance, workGroups]) => ({
    title: appliance,
    subGroups: Object.entries(workGroups).map(([work, items]) => ({
      title: work,
      items,
    })),
  }));
};

export default function Edit({ attributes, setAttributes }) {
  const {
    applianceTerm,
    workTerm,
    priceNotice,
    priceNoticeAlign,
    priceNoticeFontSize,
  } = attributes;
  const [fontSizes] = useSettings("typography.fontSizes");
  const priceNoticeFontSizeSlug = getFontSizeSlug(priceNoticeFontSize);
  const selectedFontSize = fontSizes?.find(
    ({ slug }) => slug === priceNoticeFontSizeSlug,
  );
  const priceNoticeClassName = selectedFontSize
    ? `has-${selectedFontSize.slug}-font-size`
    : undefined;
  const priceNoticeStyle = selectedFontSize
    ? { fontSize: selectedFontSize.size }
    : priceNoticeFontSize
      ? { fontSize: priceNoticeFontSize }
      : undefined;

  // Taxonomy terms via @wordpress/data (no meta needed, works correctly)
  const { applianceTerms, workTerms } = useSelect((select) => {
    const { getEntityRecords } = select("core");
    return {
      applianceTerms: getEntityRecords("taxonomy", "service_appliance_type", {
        per_page: 100,
        hide_empty: false,
      }),
      workTerms: getEntityRecords("taxonomy", "service_work_type", {
        per_page: 100,
        hide_empty: false,
      }),
    };
  }, []);

  // Services via direct apiFetch — guarantees meta fields are in the response
  const [services, setServices] = useState(null);

  useEffect(() => {
    // If filters are active but terms haven't loaded yet — wait
    if ((applianceTerm && !applianceTerms) || (workTerm && !workTerms)) {
      return;
    }

    setServices(null);

    // Determine if filters are selected
    const hasFilters = applianceTerm || workTerm;
    const perPage = hasFilters ? "100" : String(DEFAULT_PER_PAGE);

    const params = new URLSearchParams({
      per_page: perPage,
      order: "asc",
      orderby: "title",
    });

    const applianceTermId = getSelectedTermId(applianceTerms, applianceTerm);
    if (applianceTermId) {
      params.set("service_appliance_type", String(applianceTermId));
    }

    const workTermId = getSelectedTermId(workTerms, workTerm);
    if (workTermId) {
      params.set("service_work_type", String(workTermId));
    }

    let cancelled = false;

    apiFetch({ path: `/wp/v2/service?${params}` })
      .then((data) => {
        if (!cancelled) {
          setServices(Array.isArray(data) ? data : []);
        }
      })
      .catch(() => {
        if (!cancelled) {
          setServices([]);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [applianceTerm, workTerm, applianceTerms, workTerms]);

  const selectedApplianceTerm = getSelectedTermName(
    applianceTerms,
    applianceTerm,
    __("All appliance types", "ct-core"),
  );
  const selectedWorkTerm = getSelectedTermName(
    workTerms,
    workTerm,
    __("All work types", "ct-core"),
  );

  const hasFilters = applianceTerm || workTerm;

  return (
    <>
      <BlockControls>
        <AlignmentToolbar
          value={priceNoticeAlign}
          onChange={(align) => setAttributes({ priceNoticeAlign: align })}
        />
      </BlockControls>

      <InspectorControls>
        <PanelBody title={__("Price list settings", "ct-core")}>
          <SelectControl
            label={__("Appliance type", "ct-core")}
            value={applianceTerm}
            options={getTermOptions(
              applianceTerms,
              __("All appliance types", "ct-core"),
            )}
            onChange={(value) => setAttributes({ applianceTerm: value })}
          />
          <SelectControl
            label={__("Work type", "ct-core")}
            value={workTerm}
            options={getTermOptions(workTerms, __("All work types", "ct-core"))}
            onChange={(value) => setAttributes({ workTerm: value })}
          />
          <FontSizePicker
            value={priceNoticeFontSize}
            onChange={(value) =>
              setAttributes({ priceNoticeFontSize: value || "" })
            }
          />
        </PanelBody>
      </InspectorControls>

      <div {...useBlockProps()}>
        <InnerBlocks
          allowedBlocks={ALLOWED_BLOCKS}
          template={TEMPLATE}
          templateLock={false}
        />

        <div className="ct-services-pricelist__filters">
          <span>{selectedApplianceTerm}</span>
          <span>{selectedWorkTerm}</span>
        </div>

        {!hasFilters && (
          <div className="ct-services-pricelist__notice">
            {sprintf(
              __(
                "Showing first %d services. Select filters to see more.",
                "ct-core",
              ),
              DEFAULT_PER_PAGE,
            )}
          </div>
        )}

        <figure className="wp-block-table is-style-stripes">
          <table className="has-fixed-layout ct-services-pricelist__table">
            <thead>
              <tr>
                <th scope="col">{__("Service", "ct-core")}</th>
                <th scope="col">{__("Price", "ct-core")}</th>
              </tr>
            </thead>
            <tbody>
              {services === null && (
                <tr>
                  <td colSpan="2">{__("Loading list...", "ct-core")}</td>
                </tr>
              )}
              {Array.isArray(services) && services.length === 0 && (
                <tr>
                  <td colSpan="2">{__("Services not found.", "ct-core")}</td>
                </tr>
              )}
              {Array.isArray(services) &&
                services.length > 0 &&
                (() => {
                  const grouped = groupServices(
                    services,
                    applianceTerm,
                    workTerm,
                    applianceTerms,
                    workTerms,
                  );
                  // If both filters selected or grouped result has items without subGroups
                  const isTwoLevel = grouped[0]?.subGroups;

                  return grouped.map((group, groupIdx) => (
                    <React.Fragment key={groupIdx}>
                      {isTwoLevel ? (
                        <>
                          <tr className="ct-services-pricelist__group-row">
                            <td
                              colSpan="2"
                              className="ct-services-pricelist__group-title"
                            >
                              {group.title}
                            </td>
                          </tr>
                          {group.subGroups.map((subgroup, subIdx) => (
                            <React.Fragment key={subIdx}>
                              <tr className="ct-services-pricelist__subgroup-row">
                                <td
                                  colSpan="2"
                                  className="ct-services-pricelist__subgroup-title"
                                >
                                  {subgroup.title}
                                </td>
                              </tr>
                              {subgroup.items.map((service) => (
                                <tr key={service.id}>
                                  <td>
                                    {service.localized_title ||
                                      getPlainText(service.title?.rendered)}
                                  </td>
                                  <td>{service.formatted_price || ""}</td>
                                </tr>
                              ))}
                            </React.Fragment>
                          ))}
                        </>
                      ) : (
                        <>
                          {group.title && (
                            <tr className="ct-services-pricelist__group-row">
                              <td
                                colSpan="2"
                                className="ct-services-pricelist__group-title"
                              >
                                {group.title}
                              </td>
                            </tr>
                          )}
                          {group.items.map((service) => (
                            <tr key={service.id}>
                              <td>
                                {service.localized_title ||
                                  getPlainText(service.title?.rendered)}
                              </td>
                              <td>{service.formatted_price || ""}</td>
                            </tr>
                          ))}
                        </>
                      )}
                    </React.Fragment>
                  ));
                })()}
            </tbody>
          </table>
          <figcaption style={{ textAlign: priceNoticeAlign }}>
            <RichText
              value={priceNotice}
              onChange={(value) => setAttributes({ priceNotice: value })}
              placeholder={__("Add price notice...", "ct-core")}
              tagName="p"
              className={priceNoticeClassName}
              style={priceNoticeStyle}
              allowedFormats={[
                "core/bold",
                "core/italic",
                "core/link",
                "core/strikethrough",
                "core/code",
              ]}
            />
          </figcaption>
        </figure>
      </div>
    </>
  );
}
