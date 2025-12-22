import type { IProviderRegistrationData } from "src/types/marketplace";

import { useState } from "react";

import Box from "@mui/material/Box";
import Step from "@mui/material/Step";
import Chip from "@mui/material/Chip";
import Stack from "@mui/material/Stack";
import Button from "@mui/material/Button";
import Switch from "@mui/material/Switch";
import Stepper from "@mui/material/Stepper";
import { alpha } from "@mui/material/styles";
import StepLabel from "@mui/material/StepLabel";
import TextField from "@mui/material/TextField";
import Typography from "@mui/material/Typography";
import LoadingButton from "@mui/lab/LoadingButton";
import Autocomplete from "@mui/material/Autocomplete";
import InputAdornment from "@mui/material/InputAdornment";
import FormControlLabel from "@mui/material/FormControlLabel";

import { useTranslate } from "src/locales";
import {
  createProvider,
  updateProvider,
  registerProvider,
  useGetMarketplaceTags,
  useGetMarketplaceCategories,
} from "src/actions/marketplace";

import { toast } from "src/components/snackbar";
import { Iconify } from "src/components/iconify";

import { CATEGORY_ICONS } from "src/types/marketplace";

// ----------------------------------------------------------------------

// STEPS wird dynamisch mit t() erstellt

const INITIAL_DATA: IProviderRegistrationData = {
  companyName: "",
  contactEmail: "",
  contactPhone: "",
  contactPerson: "",
  description: "",
  shortDescription: "",
  logoUrl: "",
  website: "",
  isNationwide: false,
  offersRemote: false,
  categoryIds: [],
  tagIds: [],
};

// ----------------------------------------------------------------------

type Props = {
  onSuccess?: () => void;
  mode?: "register" | "admin-create" | "admin-edit";
  providerId?: number;
  initialData?: Partial<
    IProviderRegistrationData & {
      id: number;
      categories: { id: number }[];
      tags: { id: number }[];
    }
  >;
};

export function ProviderRegistrationForm({
  onSuccess,
  mode = "register",
  providerId,
  initialData,
}: Props) {
  const { t } = useTranslate("marketplace");
  const [activeStep, setActiveStep] = useState(0);

  // For edit mode, initialize with existing data
  const getInitialFormData = (): IProviderRegistrationData => {
    if (mode === "admin-edit" && initialData) {
      return {
        companyName: initialData.companyName || "",
        contactEmail: initialData.contactEmail || "",
        contactPhone: initialData.contactPhone || "",
        contactPerson: initialData.contactPerson || "",
        description: initialData.description || "",
        shortDescription: initialData.shortDescription || "",
        logoUrl: initialData.logoUrl || "",
        website: initialData.website || "",
        isNationwide: initialData.isNationwide || false,
        offersRemote: initialData.offersRemote || false,
        categoryIds:
          initialData.categories?.map((cat) => cat.id) ||
          initialData.categoryIds ||
          [],
        tagIds:
          initialData.tags?.map((tag) => tag.id) || initialData.tagIds || [],
      };
    }
    return INITIAL_DATA;
  };

  const [formData, setFormData] =
    useState<IProviderRegistrationData>(getInitialFormData);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [loading, setLoading] = useState(false);
  const [submitted, setSubmitted] = useState(false);

  const { categories } = useGetMarketplaceCategories();
  const { tags } = useGetMarketplaceTags();

  const STEPS = [
    t("registration.steps.companyData"),
    t("registration.steps.categoriesTags"),
    t("registration.steps.description"),
    t("registration.steps.summary"),
  ];

  const handleChange = (field: keyof IProviderRegistrationData, value: any) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
    if (errors[field]) {
      setErrors((prev) => {
        const newErrors = { ...prev };
        delete newErrors[field];
        return newErrors;
      });
    }
  };

  const validateStep = (step: number): boolean => {
    const newErrors: Record<string, string> = {};

    if (step === 0) {
      if (!formData.companyName.trim()) {
        newErrors.companyName = t("registration.form.companyNameRequired");
      }
      if (!formData.contactEmail.trim()) {
        newErrors.contactEmail = t("registration.form.emailRequired");
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.contactEmail)) {
        newErrors.contactEmail = t("registration.form.emailInvalid");
      }
      if (formData.website && !/^https?:\/\/.+/.test(formData.website)) {
        newErrors.website = t("registration.form.websiteInvalid");
      }
    }

    if (step === 1) {
      if (formData.categoryIds.length === 0) {
        newErrors.categoryIds = t("registration.form.categoriesRequired");
      }
    }

    if (step === 2) {
      if (!formData.description.trim()) {
        newErrors.description = t("registration.form.descriptionRequired");
      } else if (formData.description.length < 50) {
        newErrors.description = t("registration.form.descriptionMinLength");
      }
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleNext = () => {
    if (validateStep(activeStep)) {
      setActiveStep((prev) => prev + 1);
    }
  };

  const handleBack = () => {
    setActiveStep((prev) => prev - 1);
  };

  const handleSubmit = async () => {
    if (!validateStep(activeStep)) return;

    setLoading(true);
    try {
      if (mode === "admin-edit" && providerId) {
        // Admin edits existing provider
        await updateProvider(providerId, formData);
        // Don't show submitted state for edit - just close dialog
        onSuccess?.();
      } else if (mode === "admin-create") {
        // Admin creates providers with approved status by default
        await createProvider({
          ...formData,
          status: "approved",
        });
        toast.success(t("registration.success.adminMessage"));
        setSubmitted(true);
        onSuccess?.();
      } else {
        await registerProvider(formData);
        toast.success(t("registration.success.message", { hours: 24 }));
        setSubmitted(true);
        onSuccess?.();
      }
    } catch (error: any) {
      const errorMessage =
        error?.response?.data?.error ||
        error?.message ||
        (mode === "admin-edit"
          ? t("admin.messages.errorUpdate")
          : mode === "admin-create"
            ? t("admin.messages.errorCreate")
            : t("registration.form.submit"));
      toast.error(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  if (submitted) {
    return (
      <Box sx={{ py: 4, textAlign: "center" }}>
        <Box
          sx={{
            width: 100,
            height: 100,
            mx: "auto",
            mb: 3,
            borderRadius: "50%",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            background: (theme) =>
              `linear-gradient(135deg, ${alpha(theme.palette.success.light, 0.2)} 0%, ${alpha(theme.palette.success.main, 0.1)} 100%)`,
            border: (theme) => `3px solid ${theme.palette.success.main}`,
          }}
        >
          <Iconify
            icon="solar:check-circle-bold"
            width={56}
            sx={{ color: "success.main" }}
          />
        </Box>
        <Typography variant="h4" gutterBottom sx={{ fontWeight: 700 }}>
          {mode === "admin-create"
            ? t("registration.success.adminMessage")
            : t("registration.success.title")}
        </Typography>
        <Typography
          variant="body1"
          color="text.secondary"
          sx={{ maxWidth: 400, mx: "auto", mb: 3 }}
        >
          {mode === "admin-create"
            ? t("registration.success.adminMessage")
            : t("registration.success.message", { hours: 24 })}
        </Typography>
        <Box
          sx={{
            p: 2,
            borderRadius: 2,
            bgcolor: "background.neutral",
            display: "inline-flex",
            alignItems: "center",
            gap: 1,
          }}
        >
          <Iconify
            icon="solar:letter-bold"
            width={20}
            sx={{ color: "primary.main" }}
          />
          <Typography variant="body2">
            {t("registration.success.confirmationEmail", {
              email: formData.contactEmail,
            })}
          </Typography>
        </Box>
      </Box>
    );
  }

  return (
    <Box sx={{ width: "100%" }}>
      {/* Progress Steps - Compact */}
      <Stepper
        activeStep={activeStep}
        alternativeLabel
        sx={{
          mb: 3,
          px: 0,
          "& .MuiStepLabel-label": {
            fontSize: "0.75rem",
            fontWeight: 500,
          },
          "& .MuiStepIcon-root": {
            width: 28,
            height: 28,
          },
          "& .MuiStepIcon-root.Mui-active": {
            color: "primary.main",
          },
          "& .MuiStepIcon-root.Mui-completed": {
            color: "success.main",
          },
        }}
      >
        {STEPS.map((label) => (
          <Step key={label}>
            <StepLabel>{label}</StepLabel>
          </Step>
        ))}
      </Stepper>

      {activeStep === 0 && (
        <Stack spacing={2.5}>
          <Box sx={{ display: "flex", alignItems: "center", gap: 1.5, mb: 1 }}>
            <Box
              sx={{
                p: 0.75,
                borderRadius: 1,
                bgcolor: "primary.main",
                color: "primary.contrastText",
                display: "flex",
              }}
            >
              <Iconify icon="solar:buildings-bold" width={18} />
            </Box>
            <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
              {t("registration.steps.companyData")}
            </Typography>
          </Box>

          <TextField
            fullWidth
            label={t("registration.form.companyName")}
            value={formData.companyName}
            onChange={(e) => handleChange("companyName", e.target.value)}
            error={!!errors.companyName}
            helperText={errors.companyName}
            required
            InputProps={{
              startAdornment: (
                <InputAdornment position="start">
                  <Iconify
                    icon="solar:buildings-2-bold"
                    width={20}
                    sx={{ color: "text.disabled" }}
                  />
                </InputAdornment>
              ),
            }}
          />

          <Stack direction={{ xs: "column", sm: "row" }} spacing={2}>
            <TextField
              fullWidth
              label={t("registration.form.contactPerson")}
              value={formData.contactPerson}
              onChange={(e) => handleChange("contactPerson", e.target.value)}
              InputProps={{
                startAdornment: (
                  <InputAdornment position="start">
                    <Iconify
                      icon="solar:user-bold"
                      width={20}
                      sx={{ color: "text.disabled" }}
                    />
                  </InputAdornment>
                ),
              }}
            />
            <TextField
              fullWidth
              label={t("registration.form.phone")}
              value={formData.contactPhone}
              onChange={(e) => handleChange("contactPhone", e.target.value)}
              InputProps={{
                startAdornment: (
                  <InputAdornment position="start">
                    <Iconify
                      icon="solar:phone-bold"
                      width={20}
                      sx={{ color: "text.disabled" }}
                    />
                  </InputAdornment>
                ),
              }}
            />
          </Stack>

          <TextField
            fullWidth
            label={t("registration.form.email")}
            type="email"
            value={formData.contactEmail}
            onChange={(e) => handleChange("contactEmail", e.target.value)}
            error={!!errors.contactEmail}
            helperText={errors.contactEmail}
            required
            InputProps={{
              startAdornment: (
                <InputAdornment position="start">
                  <Iconify
                    icon="solar:letter-bold"
                    width={20}
                    sx={{ color: "text.disabled" }}
                  />
                </InputAdornment>
              ),
            }}
          />

          <TextField
            fullWidth
            label={t("registration.form.website")}
            value={formData.website}
            onChange={(e) => handleChange("website", e.target.value)}
            error={!!errors.website}
            helperText={errors.website}
            placeholder="https://www.beispiel.de"
            InputProps={{
              startAdornment: (
                <InputAdornment position="start">
                  <Iconify
                    icon="solar:global-bold"
                    width={20}
                    sx={{ color: "text.disabled" }}
                  />
                </InputAdornment>
              ),
            }}
          />

          <TextField
            fullWidth
            label={t("registration.form.logoUrl")}
            value={formData.logoUrl}
            onChange={(e) => handleChange("logoUrl", e.target.value)}
            helperText={t("registration.form.logoUrlDescription")}
            InputProps={{
              startAdornment: (
                <InputAdornment position="start">
                  <Iconify
                    icon="solar:gallery-bold"
                    width={20}
                    sx={{ color: "text.disabled" }}
                  />
                </InputAdornment>
              ),
            }}
          />

          <Stack
            direction="row"
            spacing={2}
            sx={{
              pt: 1,
              "& .MuiFormControlLabel-root": {
                flex: 1,
                m: 0,
                p: 1.5,
                borderRadius: 1.5,
                border: (theme) =>
                  `1px solid ${alpha(theme.palette.grey[500], 0.2)}`,
                transition: "all 0.2s",
                "&:has(.Mui-checked)": {
                  borderColor: "primary.main",
                  bgcolor: (theme) => alpha(theme.palette.primary.main, 0.08),
                },
              },
            }}
          >
            <FormControlLabel
              control={
                <Switch
                  checked={formData.isNationwide}
                  onChange={(e) =>
                    handleChange("isNationwide", e.target.checked)
                  }
                  size="small"
                />
              }
              label={
                <Stack direction="row" spacing={1} alignItems="center">
                  <Iconify
                    icon="solar:map-bold"
                    width={18}
                    sx={{
                      color: formData.isNationwide
                        ? "primary.main"
                        : "text.disabled",
                    }}
                  />
                  <Typography variant="body2" sx={{ fontWeight: 500 }}>
                    {t("registration.form.nationwide")}
                  </Typography>
                </Stack>
              }
            />
            <FormControlLabel
              control={
                <Switch
                  checked={formData.offersRemote}
                  onChange={(e) =>
                    handleChange("offersRemote", e.target.checked)
                  }
                  size="small"
                />
              }
              label={
                <Stack direction="row" spacing={1} alignItems="center">
                  <Iconify
                    icon="solar:monitor-bold"
                    width={18}
                    sx={{
                      color: formData.offersRemote
                        ? "primary.main"
                        : "text.disabled",
                    }}
                  />
                  <Typography variant="body2" sx={{ fontWeight: 500 }}>
                    {t("registration.form.remote")}
                  </Typography>
                </Stack>
              }
            />
          </Stack>
        </Stack>
      )}

      {activeStep === 1 && (
        <Stack spacing={3}>
          <Box sx={{ display: "flex", alignItems: "center", gap: 1.5, mb: 1 }}>
            <Box
              sx={{
                p: 0.75,
                borderRadius: 1,
                bgcolor: "secondary.main",
                color: "secondary.contrastText",
                display: "flex",
              }}
            >
              <Iconify icon="solar:widget-5-bold" width={18} />
            </Box>
            <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
              {t("registration.steps.categoriesTags")}
            </Typography>
          </Box>

          <Box>
            <Typography
              variant="subtitle2"
              gutterBottom
              sx={{ fontWeight: 600 }}
            >
              {t("registration.form.categoriesRequired")}
            </Typography>
            <Stack
              direction="row"
              spacing={1}
              flexWrap="wrap"
              useFlexGap
              sx={{ mt: 1.5 }}
            >
              {categories.map((category) => {
                const isSelected = formData.categoryIds.includes(category.id);
                return (
                  <Chip
                    key={category.id}
                    label={category.name}
                    icon={
                      <Iconify
                        icon={
                          CATEGORY_ICONS[category.slug] || "solar:health-bold"
                        }
                        width={18}
                      />
                    }
                    color={isSelected ? "primary" : "default"}
                    variant={isSelected ? "filled" : "outlined"}
                    onClick={() => {
                      const newIds = isSelected
                        ? formData.categoryIds.filter(
                            (id) => id !== category.id,
                          )
                        : [...formData.categoryIds, category.id];
                      handleChange("categoryIds", newIds);
                    }}
                    sx={{
                      cursor: "pointer",
                      py: 2.5,
                      px: 0.5,
                      transition: "all 0.2s",
                      "&:hover": {
                        transform: "scale(1.03)",
                        boxShadow: (theme) =>
                          `0 4px 12px ${alpha(theme.palette.primary.main, 0.2)}`,
                      },
                    }}
                  />
                );
              })}
            </Stack>
            {errors.categoryIds && (
              <Typography
                variant="caption"
                color="error"
                sx={{ mt: 1, display: "block" }}
              >
                {errors.categoryIds}
              </Typography>
            )}
          </Box>

          <Box>
            <Typography
              variant="subtitle2"
              gutterBottom
              sx={{ fontWeight: 600 }}
            >
              {t("registration.form.shortDescription")}
            </Typography>
            <Autocomplete
              multiple
              options={tags}
              getOptionLabel={(option) => option.name}
              value={tags.filter((tag) => formData.tagIds.includes(tag.id))}
              onChange={(_, newValue) => {
                handleChange(
                  "tagIds",
                  newValue.map((tag) => tag.id),
                );
              }}
              renderInput={(params) => (
                <TextField
                  {...params}
                  placeholder={t(
                    "registration.form.shortDescriptionPlaceholder",
                  )}
                  InputProps={{
                    ...params.InputProps,
                    startAdornment: (
                      <>
                        <InputAdornment position="start">
                          <Iconify
                            icon="solar:tag-bold"
                            width={20}
                            sx={{ color: "text.disabled" }}
                          />
                        </InputAdornment>
                        {params.InputProps.startAdornment}
                      </>
                    ),
                  }}
                />
              )}
              renderTags={(value, getTagProps) =>
                value.map((option, index) => (
                  <Chip
                    {...getTagProps({ index })}
                    key={option.id}
                    label={option.name}
                    size="small"
                    color="primary"
                    variant="soft"
                  />
                ))
              }
            />
          </Box>
        </Stack>
      )}

      {activeStep === 2 && (
        <Stack spacing={3}>
          <Box sx={{ display: "flex", alignItems: "center", gap: 1.5, mb: 1 }}>
            <Box
              sx={{
                p: 0.75,
                borderRadius: 1,
                bgcolor: "info.main",
                color: "info.contrastText",
                display: "flex",
              }}
            >
              <Iconify icon="solar:document-text-bold" width={18} />
            </Box>
            <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
              {t("registration.steps.description")}
            </Typography>
          </Box>

          <Box>
            <Typography
              variant="subtitle2"
              gutterBottom
              sx={{ fontWeight: 600 }}
            >
              {t("registration.form.shortDescription")}
            </Typography>
            <TextField
              fullWidth
              value={formData.shortDescription}
              onChange={(e) => handleChange("shortDescription", e.target.value)}
              multiline
              rows={2}
              placeholder={t("registration.form.shortDescriptionPlaceholder")}
              helperText={`${formData.shortDescription?.length ?? 0}/200 Zeichen – wird in der Vorschau angezeigt`}
              inputProps={{ maxLength: 200 }}
            />
          </Box>

          <Box>
            <Typography
              variant="subtitle2"
              gutterBottom
              sx={{ fontWeight: 600 }}
            >
              {t("registration.form.description")} *
            </Typography>
            <TextField
              fullWidth
              value={formData.description}
              onChange={(e) => handleChange("description", e.target.value)}
              error={!!errors.description}
              helperText={
                errors.description ||
                `${formData.description.length} Zeichen (mind. 50)`
              }
              multiline
              rows={6}
              placeholder={t("registration.form.descriptionPlaceholder")}
              sx={{
                "& .MuiOutlinedInput-root": {
                  bgcolor: "background.neutral",
                },
              }}
            />
          </Box>

          {/* Writing Tips */}
          <Box
            sx={{
              p: 2,
              borderRadius: 2,
              bgcolor: (theme) => alpha(theme.palette.info.main, 0.08),
              border: (theme) =>
                `1px solid ${alpha(theme.palette.info.main, 0.2)}`,
            }}
          >
            <Stack direction="row" spacing={1.5} alignItems="flex-start">
              <Iconify
                icon="solar:lightbulb-bolt-bold"
                width={20}
                sx={{ color: "info.main", mt: 0.25 }}
              />
              <Box>
                <Typography
                  variant="subtitle2"
                  sx={{ fontWeight: 600, mb: 0.5 }}
                >
                  {t("registration.form.descriptionTip")}
                </Typography>
                <Typography
                  variant="caption"
                  color="text.secondary"
                  sx={{ lineHeight: 1.6 }}
                >
                  {t("registration.form.descriptionTip")}
                </Typography>
              </Box>
            </Stack>
          </Box>
        </Stack>
      )}

      {activeStep === 3 && (
        <Stack spacing={3}>
          <Box sx={{ display: "flex", alignItems: "center", gap: 1.5, mb: 1 }}>
            <Box
              sx={{
                p: 0.75,
                borderRadius: 1,
                bgcolor: "success.main",
                color: "success.contrastText",
                display: "flex",
              }}
            >
              <Iconify icon="solar:checklist-bold" width={18} />
            </Box>
            <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
              Zusammenfassung
            </Typography>
          </Box>

          <Box
            sx={{
              p: 2.5,
              bgcolor: "background.neutral",
              borderRadius: 2,
              border: (theme) =>
                `1px solid ${alpha(theme.palette.grey[500], 0.12)}`,
            }}
          >
            <Stack spacing={2.5}>
              {/* Company */}
              <Stack direction="row" spacing={2} alignItems="flex-start">
                <Box
                  sx={{
                    width: 40,
                    height: 40,
                    borderRadius: 1.5,
                    bgcolor: "primary.main",
                    color: "primary.contrastText",
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                    flexShrink: 0,
                  }}
                >
                  <Iconify icon="solar:buildings-bold" width={20} />
                </Box>
                <Box sx={{ flex: 1 }}>
                  <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                    {formData.companyName}
                  </Typography>
                  <Typography variant="body2" color="text.secondary">
                    {formData.contactEmail}
                    {formData.contactPerson && ` • ${formData.contactPerson}`}
                  </Typography>
                  {formData.website && (
                    <Typography variant="caption" color="primary.main">
                      {formData.website}
                    </Typography>
                  )}
                </Box>
              </Stack>

              {/* Categories */}
              <Box>
                <Typography
                  variant="caption"
                  color="text.secondary"
                  sx={{ fontWeight: 600, mb: 1, display: "block" }}
                >
                  KATEGORIEN
                </Typography>
                <Stack
                  direction="row"
                  spacing={0.75}
                  flexWrap="wrap"
                  useFlexGap
                >
                  {categories
                    .filter((c) => formData.categoryIds.includes(c.id))
                    .map((cat) => (
                      <Chip
                        key={cat.id}
                        label={cat.name}
                        size="small"
                        color="primary"
                        icon={
                          <Iconify
                            icon={
                              CATEGORY_ICONS[cat.slug] || "solar:health-bold"
                            }
                            width={16}
                          />
                        }
                      />
                    ))}
                </Stack>
              </Box>

              {/* Tags */}
              {formData.tagIds.length > 0 && (
                <Box>
                  <Typography
                    variant="caption"
                    color="text.secondary"
                    sx={{ fontWeight: 600, mb: 1, display: "block" }}
                  >
                    TAGS
                  </Typography>
                  <Stack
                    direction="row"
                    spacing={0.5}
                    flexWrap="wrap"
                    useFlexGap
                  >
                    {tags
                      .filter((tag) => formData.tagIds.includes(tag.id))
                      .map((tagItem) => (
                        <Chip
                          key={tagItem.id}
                          label={tagItem.name}
                          size="small"
                          variant="outlined"
                        />
                      ))}
                  </Stack>
                </Box>
              )}

              {/* Features */}
              {(formData.isNationwide || formData.offersRemote) && (
                <Stack direction="row" spacing={1}>
                  {formData.isNationwide && (
                    <Chip
                      label={t("registration.form.nationwide")}
                      size="small"
                      color="info"
                      icon={<Iconify icon="solar:map-bold" width={16} />}
                    />
                  )}
                  {formData.offersRemote && (
                    <Chip
                      label={t("registration.form.remote")}
                      size="small"
                      color="secondary"
                      icon={<Iconify icon="solar:monitor-bold" width={16} />}
                    />
                  )}
                </Stack>
              )}
            </Stack>
          </Box>

          {/* Confirmation Note */}
          {mode === "admin-create" ? (
            <Box
              sx={{
                p: 2,
                borderRadius: 2,
                bgcolor: (theme) => alpha(theme.palette.success.main, 0.08),
                border: (theme) =>
                  `1px solid ${alpha(theme.palette.success.main, 0.2)}`,
              }}
            >
              <Stack direction="row" spacing={1.5} alignItems="flex-start">
                <Iconify
                  icon="solar:info-circle-bold"
                  width={20}
                  sx={{ color: "success.main", mt: 0.25 }}
                />
                <Typography variant="body2" color="text.secondary">
                  {t("registration.info.adminCreate")}
                </Typography>
              </Stack>
            </Box>
          ) : (
            <Box
              sx={{
                p: 2,
                borderRadius: 2,
                bgcolor: (theme) => alpha(theme.palette.warning.main, 0.08),
                border: (theme) =>
                  `1px solid ${alpha(theme.palette.warning.main, 0.2)}`,
              }}
            >
              <Stack direction="row" spacing={1.5} alignItems="flex-start">
                <Iconify
                  icon="solar:info-circle-bold"
                  width={20}
                  sx={{ color: "warning.main", mt: 0.25 }}
                />
                <Typography variant="body2" color="text.secondary">
                  {t("registration.info.publicRegistration")}
                </Typography>
              </Stack>
            </Box>
          )}
        </Stack>
      )}

      {/* Navigation Buttons */}
      <Stack
        direction="row"
        spacing={2}
        justifyContent="flex-end"
        sx={{
          mt: 4,
          pt: 3,
          borderTop: (theme) =>
            `1px solid ${alpha(theme.palette.divider, 0.5)}`,
        }}
      >
        {activeStep > 0 && (
          <Button
            onClick={handleBack}
            disabled={loading}
            startIcon={<Iconify icon="solar:arrow-left-bold" />}
          >
            {t("registration.form.back")}
          </Button>
        )}
        {activeStep < STEPS.length - 1 ? (
          <Button
            variant="contained"
            onClick={handleNext}
            endIcon={<Iconify icon="solar:arrow-right-bold" />}
            sx={{ minWidth: 120 }}
          >
            {t("registration.form.continue")}
          </Button>
        ) : (
          <LoadingButton
            variant="contained"
            loading={loading}
            onClick={handleSubmit}
            startIcon={<Iconify icon="solar:check-circle-bold" />}
            color="success"
            sx={{ minWidth: 200 }}
          >
            {mode === "admin-edit"
              ? t("registration.form.submitEdit")
              : mode === "admin-create"
                ? t("registration.form.submitAdmin")
                : t("registration.form.submit")}
          </LoadingButton>
        )}
      </Stack>
    </Box>
  );
}
