class Environment {
  Environment({required this.name, required String apiBaseUrl})
    : baseUrl = Uri.parse(apiBaseUrl) {
    if (!{'local', 'staging', 'production'}.contains(name) ||
        baseUrl.scheme != 'https' ||
        baseUrl.host.isEmpty ||
        baseUrl.userInfo.isNotEmpty ||
        baseUrl.hasQuery ||
        baseUrl.hasFragment ||
        !baseUrl.path.endsWith('/api/v1')) {
      throw const FormatException('Configure a valid HTTPS API base URL.');
    }
  }
  factory Environment.fromBuild() => Environment(
    name: const String.fromEnvironment('KOBI_ENV', defaultValue: 'production'),
    apiBaseUrl: const String.fromEnvironment('API_BASE_URL'),
  );
  final String name;
  final Uri baseUrl;
  String get scope => '$name|$baseUrl';
}
