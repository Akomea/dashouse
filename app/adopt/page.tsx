"use client";

import { FormEvent, ReactNode, useMemo, useState } from "react";
import {
  validateAdoptPayload,
  type AdoptFieldErrors,
  type AdoptFieldKey,
} from "@/lib/adopt";
import { useLanguage, useT } from "@/lib/i18n/language-context";
import "./adopt.css";

type Status = "idle" | "submitting" | "success" | "error";

const PDF_HREF = "/docs/katzen-adoptionsantrag.pdf";

const emptyForm = {
  fullName: "",
  dateOfBirth: "",
  phone: "",
  email: "",
  address: "",
  cityPostal: "",
  householdSize: "",
  children: "",
  childrenAges: "",
  otherAnimals: "",
  otherAnimalsDetail: "",
  homeType: "",
  livingSpace: "",
  balcony: "",
  secured: "",
  renting: "",
  landlordPermission: "",
  employmentStatus: "",
  profession: "",
  hoursAway: "",
  hadCats: "",
  hadCatsDetail: "",
  specialNeeds: "",
  whyAdopt: "",
  whichCats: "",
  anythingElse: "",
  consent: false,
  placeDate: "",
  signature: "",
};

type FormState = typeof emptyForm;

function scrollToFirstError(errors: AdoptFieldErrors) {
  const first = Object.keys(errors)[0];
  if (!first) return;
  const el = document.querySelector(`[data-field="${first}"]`);
  if (el instanceof HTMLElement) {
    el.scrollIntoView({ behavior: "smooth", block: "center" });
  }
}

export default function AdoptPage() {
  const t = useT();
  const { locale } = useLanguage();
  const [form, setForm] = useState<FormState>(emptyForm);
  const [status, setStatus] = useState<Status>("idle");
  const [errorMsg, setErrorMsg] = useState("");
  const [fieldErrors, setFieldErrors] = useState<AdoptFieldErrors>({});

  const f = t.adopt.fields;

  const messageForCode = (code: string) => {
    if (code === "email") return t.adopt.fieldEmail;
    if (code === "consent") return t.adopt.fieldConsent;
    return t.adopt.fieldRequired;
  };

  const localizeErrors = (errors: AdoptFieldErrors): AdoptFieldErrors => {
    const next: AdoptFieldErrors = {};
    for (const [key, code] of Object.entries(errors) as [AdoptFieldKey, string][]) {
      next[key] = messageForCode(code);
    }
    return next;
  };

  const set =
    (key: keyof FormState) =>
    (value: string | boolean) => {
      setForm((prev) => ({ ...prev, [key]: value }));
      setFieldErrors((prev) => {
        if (!prev[key as AdoptFieldKey]) return prev;
        const next = { ...prev };
        delete next[key as AdoptFieldKey];
        return next;
      });
      if (status === "error" || status === "success") {
        setStatus("idle");
        setErrorMsg("");
      }
    };

  const householdOptions = useMemo(() => ["1", "2", "3", "4", "5+"], []);

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setErrorMsg("");

    const payload = {
      ...form,
      consent: form.consent === true,
      locale,
    };

    const clientCheck = validateAdoptPayload(payload);
    if (!clientCheck.success) {
      const localized = localizeErrors(clientCheck.fieldErrors);
      setFieldErrors(localized);
      setStatus("error");
      setErrorMsg(t.adopt.validationFix);
      scrollToFirstError(localized);
      return;
    }

    setStatus("submitting");
    setFieldErrors({});

    try {
      const res = await fetch("/api/adopt", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      const data = await res.json().catch(() => ({}));

      if (data.code === "VALIDATION_ERROR" && data.fieldErrors) {
        const localized = localizeErrors(data.fieldErrors as AdoptFieldErrors);
        setFieldErrors(localized);
        setStatus("error");
        setErrorMsg(t.adopt.validationFix);
        scrollToFirstError(localized);
        return;
      }

      if (!res.ok || data.success === false) {
        setStatus("error");
        setErrorMsg(typeof data.error === "string" ? data.error : t.adopt.error);
        return;
      }

      setStatus("success");
      setForm(emptyForm);
      setFieldErrors({});
    } catch {
      setStatus("error");
      setErrorMsg(t.adopt.error);
    }
  }

  const err = (key: AdoptFieldKey) => fieldErrors[key];

  return (
    <div className="adopt-page">
      <header className="adopt-hero">
        <h1>{t.adopt.title}</h1>
        <p>{t.adopt.subtitle}</p>
        <div className="adopt-toolbar">
          <a className="adopt-btn adopt-btn-ghost" href={PDF_HREF} download>
            {t.adopt.download}
          </a>
        </div>
      </header>

      <div className="adopt-sheet-wrap">
        <div className="adopt-sheet">
          <p className="adopt-intro">{t.adopt.intro}</p>
          <p className="adopt-required-note">{t.adopt.requiredNote}</p>

          <form onSubmit={onSubmit} noValidate>
            <div className="adopt-columns">
              <div>
                <section className="adopt-section">
                  <div className="adopt-section-head" data-tone="1">
                    {t.adopt.sections.personal}
                  </div>
                  <div className="adopt-section-body">
                    <Field label={`${f.fullName} *`} error={err("fullName")} fieldKey="fullName">
                      <input
                        type="text"
                        autoComplete="name"
                        aria-invalid={Boolean(err("fullName"))}
                        value={form.fullName}
                        onChange={(e) => set("fullName")(e.target.value)}
                      />
                    </Field>
                    <div className="adopt-row two">
                      <Field label={`${f.dateOfBirth} *`} error={err("dateOfBirth")} fieldKey="dateOfBirth">
                        <input
                          type="date"
                          aria-invalid={Boolean(err("dateOfBirth"))}
                          value={form.dateOfBirth}
                          onChange={(e) => set("dateOfBirth")(e.target.value)}
                        />
                      </Field>
                      <Field label={`${f.phone} *`} error={err("phone")} fieldKey="phone">
                        <input
                          type="tel"
                          autoComplete="tel"
                          aria-invalid={Boolean(err("phone"))}
                          value={form.phone}
                          onChange={(e) => set("phone")(e.target.value)}
                        />
                      </Field>
                    </div>
                    <Field label={`${f.email} *`} error={err("email")} fieldKey="email">
                      <input
                        type="email"
                        autoComplete="email"
                        aria-invalid={Boolean(err("email"))}
                        value={form.email}
                        onChange={(e) => set("email")(e.target.value)}
                      />
                    </Field>
                    <Field label={`${f.address} *`} error={err("address")} fieldKey="address">
                      <input
                        type="text"
                        autoComplete="street-address"
                        aria-invalid={Boolean(err("address"))}
                        value={form.address}
                        onChange={(e) => set("address")(e.target.value)}
                      />
                    </Field>
                    <Field label={`${f.cityPostal} *`} error={err("cityPostal")} fieldKey="cityPostal">
                      <input
                        type="text"
                        autoComplete="postal-code"
                        aria-invalid={Boolean(err("cityPostal"))}
                        value={form.cityPostal}
                        onChange={(e) => set("cityPostal")(e.target.value)}
                      />
                    </Field>
                  </div>
                </section>

                <section className="adopt-section">
                  <div className="adopt-section-head" data-tone="2">
                    {t.adopt.sections.household}
                  </div>
                  <div className="adopt-section-body">
                    <ChoiceGroup
                      legend={`${f.householdSize} *`}
                      name="householdSize"
                      error={err("householdSize")}
                      fieldKey="householdSize"
                    >
                      {householdOptions.map((n) => (
                        <Choice
                          key={n}
                          name="householdSize"
                          checked={form.householdSize === n}
                          onChange={() => set("householdSize")(n)}
                          label={n}
                        />
                      ))}
                    </ChoiceGroup>
                    <ChoiceGroup
                      legend={`${f.children} *`}
                      name="children"
                      error={err("children")}
                      fieldKey="children"
                    >
                      <Choice
                        name="children"
                        checked={form.children === "yes"}
                        onChange={() => set("children")("yes")}
                        label={t.adopt.yes}
                      />
                      <Choice
                        name="children"
                        checked={form.children === "no"}
                        onChange={() => set("children")("no")}
                        label={t.adopt.no}
                      />
                    </ChoiceGroup>
                    {form.children === "yes" && (
                      <Field label={`${f.childrenAges} *`} error={err("childrenAges")} fieldKey="childrenAges">
                        <input
                          type="text"
                          aria-invalid={Boolean(err("childrenAges"))}
                          value={form.childrenAges}
                          onChange={(e) => set("childrenAges")(e.target.value)}
                        />
                      </Field>
                    )}
                    <ChoiceGroup
                      legend={`${f.otherAnimals} *`}
                      name="otherAnimals"
                      error={err("otherAnimals")}
                      fieldKey="otherAnimals"
                    >
                      <Choice
                        name="otherAnimals"
                        checked={form.otherAnimals === "yes"}
                        onChange={() => set("otherAnimals")("yes")}
                        label={t.adopt.yes}
                      />
                      <Choice
                        name="otherAnimals"
                        checked={form.otherAnimals === "no"}
                        onChange={() => set("otherAnimals")("no")}
                        label={t.adopt.no}
                      />
                    </ChoiceGroup>
                    {form.otherAnimals === "yes" && (
                      <Field
                        label={`${f.otherAnimalsDetail} *`}
                        error={err("otherAnimalsDetail")}
                        fieldKey="otherAnimalsDetail"
                      >
                        <input
                          type="text"
                          aria-invalid={Boolean(err("otherAnimalsDetail"))}
                          value={form.otherAnimalsDetail}
                          onChange={(e) => set("otherAnimalsDetail")(e.target.value)}
                        />
                      </Field>
                    )}
                  </div>
                </section>

                <section className="adopt-section">
                  <div className="adopt-section-head" data-tone="3">
                    {t.adopt.sections.living}
                  </div>
                  <div className="adopt-section-body">
                    <ChoiceGroup
                      legend={`${f.homeType} *`}
                      name="homeType"
                      error={err("homeType")}
                      fieldKey="homeType"
                    >
                      <Choice
                        name="homeType"
                        checked={form.homeType === "apartment"}
                        onChange={() => set("homeType")("apartment")}
                        label={f.apartment}
                      />
                      <Choice
                        name="homeType"
                        checked={form.homeType === "house"}
                        onChange={() => set("homeType")("house")}
                        label={f.house}
                      />
                    </ChoiceGroup>
                    <Field label={`${f.livingSpace} *`} error={err("livingSpace")} fieldKey="livingSpace">
                      <input
                        type="text"
                        inputMode="numeric"
                        aria-invalid={Boolean(err("livingSpace"))}
                        value={form.livingSpace}
                        onChange={(e) => set("livingSpace")(e.target.value)}
                      />
                    </Field>
                    <ChoiceGroup legend={`${f.balcony} *`} name="balcony" error={err("balcony")} fieldKey="balcony">
                      <Choice
                        name="balcony"
                        checked={form.balcony === "yes"}
                        onChange={() => set("balcony")("yes")}
                        label={t.adopt.yes}
                      />
                      <Choice
                        name="balcony"
                        checked={form.balcony === "no"}
                        onChange={() => set("balcony")("no")}
                        label={t.adopt.no}
                      />
                    </ChoiceGroup>
                    <ChoiceGroup legend={`${f.secured} *`} name="secured" error={err("secured")} fieldKey="secured">
                      <Choice
                        name="secured"
                        checked={form.secured === "yes"}
                        onChange={() => set("secured")("yes")}
                        label={t.adopt.yes}
                      />
                      <Choice
                        name="secured"
                        checked={form.secured === "no"}
                        onChange={() => set("secured")("no")}
                        label={t.adopt.no}
                      />
                      <Choice
                        name="secured"
                        checked={form.secured === "na"}
                        onChange={() => set("secured")("na")}
                        label={t.adopt.notApplicable}
                      />
                    </ChoiceGroup>
                    <ChoiceGroup legend={`${f.renting} *`} name="renting" error={err("renting")} fieldKey="renting">
                      <Choice
                        name="renting"
                        checked={form.renting === "yes"}
                        onChange={() => set("renting")("yes")}
                        label={t.adopt.yes}
                      />
                      <Choice
                        name="renting"
                        checked={form.renting === "no"}
                        onChange={() => {
                          set("renting")("no");
                          set("landlordPermission")("");
                        }}
                        label={t.adopt.no}
                      />
                    </ChoiceGroup>
                    {form.renting === "yes" && (
                      <ChoiceGroup
                        legend={`${f.landlordPermission} *`}
                        name="landlordPermission"
                        error={err("landlordPermission")}
                        fieldKey="landlordPermission"
                      >
                        <Choice
                          name="landlordPermission"
                          checked={form.landlordPermission === "yes"}
                          onChange={() => set("landlordPermission")("yes")}
                          label={t.adopt.yes}
                        />
                        <Choice
                          name="landlordPermission"
                          checked={form.landlordPermission === "no"}
                          onChange={() => set("landlordPermission")("no")}
                          label={t.adopt.no}
                        />
                        <Choice
                          name="landlordPermission"
                          checked={form.landlordPermission === "pending"}
                          onChange={() => set("landlordPermission")("pending")}
                          label={t.adopt.notYet}
                        />
                      </ChoiceGroup>
                    )}
                  </div>
                </section>
              </div>

              <div>
                <section className="adopt-section">
                  <div className="adopt-section-head" data-tone="4">
                    {t.adopt.sections.employment}
                  </div>
                  <div className="adopt-section-body">
                    <ChoiceGroup
                      legend={`${f.employmentStatus} *`}
                      name="employmentStatus"
                      error={err("employmentStatus")}
                      fieldKey="employmentStatus"
                    >
                      {(
                        [
                          ["fullTime", f.fullTime],
                          ["partTime", f.partTime],
                          ["selfEmployed", f.selfEmployed],
                          ["student", f.student],
                          ["vocationalTraining", f.vocationalTraining],
                          ["notEmployed", f.notEmployed],
                        ] as const
                      ).map(([value, label]) => (
                        <Choice
                          key={value}
                          name="employmentStatus"
                          checked={form.employmentStatus === value}
                          onChange={() => set("employmentStatus")(value)}
                          label={label}
                        />
                      ))}
                    </ChoiceGroup>
                    <Field label={f.profession} fieldKey="profession">
                      <input
                        type="text"
                        value={form.profession}
                        onChange={(e) => set("profession")(e.target.value)}
                      />
                    </Field>
                    <ChoiceGroup
                      legend={`${f.hoursAway} *`}
                      name="hoursAway"
                      error={err("hoursAway")}
                      fieldKey="hoursAway"
                    >
                      {(
                        [
                          ["lessThan4", f.lessThan4],
                          ["hours4to6", f.hours4to6],
                          ["hours6to8", f.hours6to8],
                          ["moreThan8", f.moreThan8],
                        ] as const
                      ).map(([value, label]) => (
                        <Choice
                          key={value}
                          name="hoursAway"
                          checked={form.hoursAway === value}
                          onChange={() => set("hoursAway")(value)}
                          label={label}
                        />
                      ))}
                    </ChoiceGroup>
                  </div>
                </section>

                <section className="adopt-section">
                  <div className="adopt-section-head" data-tone="5">
                    {t.adopt.sections.experience}
                  </div>
                  <div className="adopt-section-body">
                    <ChoiceGroup legend={`${f.hadCats} *`} name="hadCats" error={err("hadCats")} fieldKey="hadCats">
                      <Choice
                        name="hadCats"
                        checked={form.hadCats === "yes"}
                        onChange={() => set("hadCats")("yes")}
                        label={t.adopt.yes}
                      />
                      <Choice
                        name="hadCats"
                        checked={form.hadCats === "no"}
                        onChange={() => set("hadCats")("no")}
                        label={t.adopt.no}
                      />
                    </ChoiceGroup>
                    {form.hadCats === "yes" && (
                      <Field label={`${f.hadCatsDetail} *`} error={err("hadCatsDetail")} fieldKey="hadCatsDetail">
                        <textarea
                          aria-invalid={Boolean(err("hadCatsDetail"))}
                          value={form.hadCatsDetail}
                          onChange={(e) => set("hadCatsDetail")(e.target.value)}
                        />
                      </Field>
                    )}
                    <ChoiceGroup
                      legend={`${f.specialNeeds} *`}
                      name="specialNeeds"
                      error={err("specialNeeds")}
                      fieldKey="specialNeeds"
                    >
                      <Choice
                        name="specialNeeds"
                        checked={form.specialNeeds === "yes"}
                        onChange={() => set("specialNeeds")("yes")}
                        label={t.adopt.yes}
                      />
                      <Choice
                        name="specialNeeds"
                        checked={form.specialNeeds === "no"}
                        onChange={() => set("specialNeeds")("no")}
                        label={t.adopt.no}
                      />
                    </ChoiceGroup>
                  </div>
                </section>

                <section className="adopt-section">
                  <div className="adopt-section-head" data-tone="6">
                    {t.adopt.sections.motivation}
                  </div>
                  <div className="adopt-section-body">
                    <Field label={`${f.whyAdopt} *`} error={err("whyAdopt")} fieldKey="whyAdopt">
                      <textarea
                        aria-invalid={Boolean(err("whyAdopt"))}
                        value={form.whyAdopt}
                        onChange={(e) => set("whyAdopt")(e.target.value)}
                      />
                    </Field>
                    <Field label={f.whichCats} fieldKey="whichCats">
                      <textarea
                        value={form.whichCats}
                        onChange={(e) => set("whichCats")(e.target.value)}
                      />
                    </Field>
                  </div>
                </section>

                <section className="adopt-section">
                  <div className="adopt-section-head" data-tone="7">
                    {t.adopt.sections.additional}
                  </div>
                  <div className="adopt-section-body">
                    <Field label={f.anythingElse} fieldKey="anythingElse">
                      <textarea
                        value={form.anythingElse}
                        onChange={(e) => set("anythingElse")(e.target.value)}
                      />
                    </Field>
                    <div
                      className={`adopt-consent${err("consent") ? " has-error" : ""}`}
                      data-field="consent"
                    >
                      <input
                        id="adopt-consent"
                        type="checkbox"
                        aria-invalid={Boolean(err("consent"))}
                        checked={form.consent}
                        onChange={(e) => set("consent")(e.target.checked)}
                      />
                      <label htmlFor="adopt-consent">{f.consent} *</label>
                      {err("consent") ? <p className="adopt-field-error">{err("consent")}</p> : null}
                    </div>
                    <div className="adopt-footer-fields">
                      <Field label={`${f.placeDate} *`} error={err("placeDate")} fieldKey="placeDate">
                        <input
                          type="text"
                          aria-invalid={Boolean(err("placeDate"))}
                          value={form.placeDate}
                          onChange={(e) => set("placeDate")(e.target.value)}
                        />
                      </Field>
                      <Field label={`${f.signature} *`} error={err("signature")} fieldKey="signature">
                        <input
                          type="text"
                          aria-invalid={Boolean(err("signature"))}
                          value={form.signature}
                          onChange={(e) => set("signature")(e.target.value)}
                        />
                      </Field>
                    </div>
                  </div>
                </section>
              </div>
            </div>

            <div className="adopt-actions">
              <button
                type="submit"
                className="adopt-btn adopt-btn-primary"
                disabled={status === "submitting"}
              >
                {status === "submitting" ? t.adopt.submitting : t.adopt.submit}
              </button>
              <a className="adopt-btn adopt-btn-ghost" href={PDF_HREF} download>
                {t.adopt.download}
              </a>
            </div>

            {status === "success" && <div className="adopt-status ok">{t.adopt.success}</div>}
            {status === "error" && (
              <div className="adopt-status err" role="alert">
                <p>{errorMsg || t.adopt.error}</p>
              </div>
            )}
          </form>
        </div>
      </div>
    </div>
  );
}

function Field({
  label,
  children,
  error,
  fieldKey,
}: {
  label: string;
  children: ReactNode;
  error?: string;
  fieldKey: string;
}) {
  return (
    <div className={`adopt-field${error ? " has-error" : ""}`} data-field={fieldKey}>
      <label>
        {label}
        {children}
      </label>
      {error ? <p className="adopt-field-error">{error}</p> : null}
    </div>
  );
}

function ChoiceGroup({
  legend,
  name,
  children,
  error,
  fieldKey,
}: {
  legend: string;
  name: string;
  children: ReactNode;
  error?: string;
  fieldKey: string;
}) {
  return (
    <fieldset
      className={`adopt-fieldset${error ? " has-error" : ""}`}
      name={name}
      data-field={fieldKey}
      aria-invalid={Boolean(error)}
    >
      <legend>{legend}</legend>
      <div className="adopt-choices">{children}</div>
      {error ? <p className="adopt-field-error">{error}</p> : null}
    </fieldset>
  );
}

function Choice({
  name,
  label,
  checked,
  onChange,
}: {
  name: string;
  label: string;
  checked: boolean;
  onChange: () => void;
}) {
  return (
    <label className="adopt-choice">
      <input type="radio" name={name} checked={checked} onChange={onChange} />
      {label}
    </label>
  );
}
