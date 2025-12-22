import Box from "@mui/material/Box";
import Card from "@mui/material/Card";
import Grid from "@mui/material/Grid";
import Stack from "@mui/material/Stack";
import Button from "@mui/material/Button";
import Container from "@mui/material/Container";
import Typography from "@mui/material/Typography";
import CardContent from "@mui/material/CardContent";
import { alpha, useTheme } from "@mui/material/styles";

import { paths } from "src/routes/paths";
import { RouterLink } from "src/routes/components";

import { gradients, brandColors } from "src/theme/gradients";

import { Iconify } from "src/components/iconify";

// ----------------------------------------------------------------------

const CATEGORIES = [
  { name: "Bewegung", icon: "solar:running-round-bold", color: "#22C55E" },
  { name: "Ernährung", icon: "solar:apple-bold", color: "#F59E0B" },
  { name: "Mentale Gesundheit", icon: "solar:brain-bold", color: "#8B5CF6" },
  { name: "Ergonomie", icon: "solar:chair-bold", color: "#3B82F6" },
  { name: "Suchtprävention", icon: "solar:heart-pulse-bold", color: "#EF4444" },
];

const BENEFITS = [
  {
    icon: "solar:users-group-rounded-bold",
    title: "Reichweite",
    description:
      "Erreichen Sie tausende Unternehmen, die aktiv nach BGM-Lösungen suchen.",
    color: "#3B82F6",
  },
  {
    icon: "solar:verified-check-bold",
    title: "Verifiziert",
    description:
      "Ihr Profil wird geprüft und als vertrauenswürdiger Anbieter gelistet.",
    color: "#22C55E",
  },
  {
    icon: "solar:hand-money-bold",
    title: "Kostenlos",
    description:
      "Die Registrierung und Listung ist für Sie vollkommen kostenlos.",
    color: "#F59E0B",
  },
  {
    icon: "solar:clock-circle-bold",
    title: "Schnell online",
    description:
      "Freigabe innerhalb von 24 Stunden nach Prüfung Ihrer Angaben.",
    color: "#8B5CF6",
  },
  {
    icon: "solar:chart-bold",
    title: "Analytics",
    description: "Sehen Sie, wie oft Ihr Profil aufgerufen und angefragt wird.",
    color: "#06B6D4",
  },
  {
    icon: "solar:bell-bold",
    title: "Anfragen erhalten",
    description:
      "Erhalten Sie direkte Anfragen von interessierten Unternehmen.",
    color: "#EC4899",
  },
];

const PARTNER_TYPES = [
  {
    icon: "solar:users-group-rounded-bold",
    title: "Beratungsunternehmen",
    description:
      "Als BGM-Berater können Sie EasyBGM als digitale Lösung in Ihre Beratungsleistungen integrieren.",
  },
  {
    icon: "solar:heart-pulse-bold",
    title: "Gesundheitsdienstleister",
    description:
      "Fitnessstudios, Gesundheitszentren und Physiotherapeuten können ihre Leistungen anbieten.",
  },
  {
    icon: "solar:case-bold",
    title: "HR-Dienstleister",
    description:
      "Personalberatungen und HR-Dienstleister können EasyBGM als Teil ihres Portfolios nutzen.",
  },
  {
    icon: "solar:meditation-round-bold",
    title: "Coaches & Trainer",
    description:
      "Gesundheits-, Stress- und Ernährungscoaches finden hier ihre Zielgruppe.",
  },
];

export function PartnerView() {
  const theme = useTheme();
  const isDarkMode = theme.palette.mode === "dark";

  return (
    <>
      {/* Hero Section with Bold Gradient */}
      <Box
        component="section"
        sx={{
          position: "relative",
          py: { xs: 10, md: 16 },
          overflow: "hidden",
          background: (th) =>
            th.palette.mode === "dark"
              ? gradients.marketplace.dark
              : gradients.marketplace.light,
          color: (th) => (th.palette.mode === "dark" ? "#fff" : "#1a1a2e"),
        }}
      >
        {/* Decorative Blobs - More vibrant */}
        <Box
          sx={{
            position: "absolute",
            top: -80,
            right: -80,
            width: 350,
            height: 350,
            borderRadius: "50%",
            background: "rgba(255, 255, 255, 0.15)",
            filter: "blur(40px)",
          }}
        />
        <Box
          sx={{
            position: "absolute",
            bottom: -60,
            left: -60,
            width: 300,
            height: 300,
            borderRadius: "50%",
            background: "rgba(255, 255, 255, 0.1)",
            filter: "blur(35px)",
          }}
        />
        <Box
          sx={{
            position: "absolute",
            top: "50%",
            right: "10%",
            width: 150,
            height: 150,
            borderRadius: "50%",
            background: "rgba(255, 255, 255, 0.08)",
            filter: "blur(25px)",
          }}
        />

        <Container maxWidth="lg" sx={{ position: "relative", zIndex: 2 }}>
          <Stack alignItems="center" textAlign="center">
            <Typography
              variant="overline"
              sx={{
                mb: 2,
                px: 2.5,
                py: 0.75,
                borderRadius: 5,
                bgcolor: "rgba(255, 255, 255, 0.2)",
                backdropFilter: "blur(10px)",
                color: "#fff",
                fontWeight: 700,
                letterSpacing: 2,
                border: "1px solid rgba(255, 255, 255, 0.3)",
              }}
            >
              🚀 BGM-Marktplatz
            </Typography>

            <Typography
              variant="h1"
              component="h1"
              sx={{
                mb: 3,
                fontWeight: 800,
                fontSize: { xs: "2.5rem", md: "3.5rem" },
                lineHeight: 1.2,
                maxWidth: 800,
                color: "#fff",
                textShadow: "0 2px 20px rgba(0,0,0,0.2)",
              }}
            >
              Werden Sie Teil unseres Dienstleister-Netzwerks
            </Typography>

            <Typography
              variant="h5"
              sx={{
                mb: 4,
                maxWidth: 700,
                fontWeight: 400,
                lineHeight: 1.7,
                color: "rgba(255, 255, 255, 0.9)",
              }}
            >
              Präsentieren Sie Ihre BGM-Leistungen tausenden von Unternehmen.
              Kostenlose Registrierung – Freigabe innerhalb von 24 Stunden.
            </Typography>

            {/* Category Pills - Glass effect */}
            <Stack
              direction="row"
              spacing={1}
              flexWrap="wrap"
              useFlexGap
              justifyContent="center"
              sx={{ mb: 5 }}
            >
              {CATEGORIES.map((cat) => (
                <Box
                  key={cat.name}
                  sx={{
                    display: "flex",
                    alignItems: "center",
                    gap: 0.75,
                    px: 2,
                    py: 1,
                    borderRadius: 3,
                    bgcolor: "rgba(255, 255, 255, 0.15)",
                    backdropFilter: "blur(10px)",
                    border: "1px solid rgba(255, 255, 255, 0.25)",
                    transition: "all 0.2s",
                    "&:hover": {
                      transform: "translateY(-2px)",
                      bgcolor: "rgba(255, 255, 255, 0.25)",
                      boxShadow: "0 8px 20px rgba(0,0,0,0.15)",
                    },
                  }}
                >
                  <Iconify icon={cat.icon} width={20} sx={{ color: "#fff" }} />
                  <Typography
                    variant="body2"
                    sx={{ fontWeight: 600, color: "#fff" }}
                  >
                    {cat.name}
                  </Typography>
                </Box>
              ))}
            </Stack>

            <Stack direction={{ xs: "column", sm: "row" }} spacing={2}>
              <Button
                component={RouterLink}
                href={paths.marketplace.register}
                variant="contained"
                size="large"
                startIcon={<Iconify icon="solar:pen-new-square-bold" />}
                sx={{
                  px: 4,
                  py: 1.5,
                  fontSize: "1rem",
                  bgcolor: isDarkMode
                    ? alpha(theme.palette.background.paper, 0.9)
                    : "#fff",
                  color: brandColors.primary,
                  fontWeight: 700,
                  boxShadow: "0 8px 32px rgba(0,0,0,0.2)",
                  "&:hover": {
                    bgcolor: isDarkMode
                      ? alpha(theme.palette.background.paper, 0.95)
                      : "#fff",
                    transform: "translateY(-3px)",
                    boxShadow: "0 12px 40px rgba(0,0,0,0.3)",
                  },
                  transition: "all 0.2s",
                }}
              >
                Jetzt kostenlos registrieren
              </Button>
              <Button
                component={RouterLink}
                href="/#marketplace"
                variant="outlined"
                size="large"
                startIcon={<Iconify icon="solar:eye-bold" />}
                sx={{
                  px: 4,
                  py: 1.5,
                  color: "#fff",
                  borderColor: "rgba(255, 255, 255, 0.5)",
                  "&:hover": {
                    borderColor: "#fff",
                    bgcolor: "rgba(255, 255, 255, 0.1)",
                  },
                }}
              >
                Marktplatz ansehen
              </Button>
            </Stack>
          </Stack>
        </Container>
      </Box>

      {/* Benefits Section */}
      <Box
        component="section"
        sx={{
          py: { xs: 10, md: 14 },
          bgcolor: "background.neutral",
        }}
      >
        <Container maxWidth="lg">
          <Stack alignItems="center" textAlign="center" sx={{ mb: 8 }}>
            <Typography
              variant="h3"
              component="h2"
              sx={{ mb: 2, fontWeight: 700 }}
            >
              Ihre Vorteile auf einen Blick
            </Typography>
            <Typography
              variant="body1"
              color="text.secondary"
              sx={{ maxWidth: 600 }}
            >
              Als Partner im EasyBGM-Marktplatz profitieren Sie von zahlreichen
              Vorteilen – ohne versteckte Kosten.
            </Typography>
          </Stack>

          <Grid container spacing={3}>
            {BENEFITS.map((benefit) => (
              <Grid key={benefit.title} size={{ xs: 12, sm: 6, md: 4 }}>
                <Card
                  sx={{
                    height: "100%",
                    transition: "all 0.3s",
                    "&:hover": {
                      transform: "translateY(-4px)",
                      boxShadow: (th) =>
                        `0 12px 32px ${alpha(th.palette.grey[500], 0.12)}`,
                    },
                  }}
                >
                  <CardContent sx={{ p: 3 }}>
                    <Box
                      sx={{
                        width: 56,
                        height: 56,
                        borderRadius: 2,
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "center",
                        bgcolor: alpha(benefit.color, 0.1),
                        mb: 2,
                      }}
                    >
                      <Iconify
                        icon={benefit.icon}
                        width={28}
                        sx={{ color: benefit.color }}
                      />
                    </Box>
                    <Typography variant="h6" sx={{ mb: 1, fontWeight: 700 }}>
                      {benefit.title}
                    </Typography>
                    <Typography
                      variant="body2"
                      color="text.secondary"
                      sx={{ lineHeight: 1.7 }}
                    >
                      {benefit.description}
                    </Typography>
                  </CardContent>
                </Card>
              </Grid>
            ))}
          </Grid>
        </Container>
      </Box>

      {/* Partner Types Section */}
      <Box
        component="section"
        sx={{
          py: { xs: 10, md: 14 },
          bgcolor: "background.default",
        }}
      >
        <Container maxWidth="lg">
          <Stack alignItems="center" textAlign="center" sx={{ mb: 8 }}>
            <Typography
              variant="h3"
              component="h2"
              sx={{ mb: 2, fontWeight: 700 }}
            >
              Für wen ist der Marktplatz?
            </Typography>
            <Typography
              variant="body1"
              color="text.secondary"
              sx={{ maxWidth: 600 }}
            >
              Wir suchen qualifizierte Dienstleister, die Unternehmen bei ihrem
              Betrieblichen Gesundheitsmanagement unterstützen.
            </Typography>
          </Stack>

          <Grid container spacing={3}>
            {PARTNER_TYPES.map((type) => (
              <Grid key={type.title} size={{ xs: 12, sm: 6 }}>
                <Card
                  sx={{
                    height: "100%",
                    border: (th) =>
                      `1px solid ${alpha(th.palette.grey[500], 0.1)}`,
                    transition: "all 0.3s",
                    "&:hover": {
                      borderColor: "primary.main",
                      boxShadow: (th) => `0 0 0 1px ${th.palette.primary.main}`,
                    },
                  }}
                >
                  <CardContent sx={{ p: 3 }}>
                    <Stack direction="row" spacing={2} alignItems="flex-start">
                      <Box
                        sx={{
                          p: 1.5,
                          borderRadius: 2,
                          bgcolor: (th) => alpha(th.palette.primary.main, 0.1),
                          color: "primary.main",
                          display: "flex",
                          flexShrink: 0,
                        }}
                      >
                        <Iconify icon={type.icon} width={24} />
                      </Box>
                      <Box>
                        <Typography
                          variant="h6"
                          sx={{ mb: 1, fontWeight: 700 }}
                        >
                          {type.title}
                        </Typography>
                        <Typography
                          variant="body2"
                          color="text.secondary"
                          sx={{ lineHeight: 1.7 }}
                        >
                          {type.description}
                        </Typography>
                      </Box>
                    </Stack>
                  </CardContent>
                </Card>
              </Grid>
            ))}
          </Grid>
        </Container>
      </Box>

      {/* CTA Section */}
      <Box
        component="section"
        sx={{
          py: { xs: 10, md: 14 },
          position: "relative",
          overflow: "hidden",
          background: (th) =>
            `linear-gradient(135deg, ${th.palette.primary.dark} 0%, ${th.palette.primary.main} 100%)`,
          color: "primary.contrastText",
        }}
      >
        {/* Decorative Pattern */}
        <Box
          sx={{
            position: "absolute",
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            opacity: 0.1,
            backgroundImage: `radial-gradient(circle at 2px 2px, white 1px, transparent 0)`,
            backgroundSize: "32px 32px",
          }}
        />

        <Container maxWidth="md" sx={{ position: "relative", zIndex: 1 }}>
          <Stack alignItems="center" textAlign="center">
            <Typography
              variant="h2"
              component="h2"
              sx={{ mb: 3, fontWeight: 700 }}
            >
              Bereit loszulegen?
            </Typography>
            <Typography
              variant="h6"
              sx={{ mb: 5, opacity: 0.9, fontWeight: 400, maxWidth: 600 }}
            >
              In nur 5 Minuten registriert. Nach der Freigabe erreichen Sie
              sofort Unternehmen, die nach BGM-Dienstleistungen suchen.
            </Typography>
            <Button
              component={RouterLink}
              href={paths.marketplace.register}
              variant="contained"
              size="large"
              startIcon={<Iconify icon="solar:arrow-right-bold" />}
              sx={{
                px: 5,
                py: 2,
                fontSize: "1.1rem",
                bgcolor: "common.white",
                color: "primary.main",
                boxShadow: "0 8px 24px rgba(0,0,0,0.2)",
                "&:hover": {
                  bgcolor: "grey.100",
                  transform: "translateY(-2px)",
                  boxShadow: "0 12px 32px rgba(0,0,0,0.3)",
                },
                transition: "all 0.2s",
              }}
            >
              Jetzt kostenlos registrieren
            </Button>
          </Stack>
        </Container>
      </Box>
    </>
  );
}
