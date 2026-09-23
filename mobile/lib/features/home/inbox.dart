import 'package:flutter/material.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/features/home/favorites_page.dart';
import 'package:lens/features/home/notifications_page.dart';

void openFavorites(BuildContext context, HomeData home) {
  Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => FavoritesPage(home: home)));
}

void openNotifications(BuildContext context, HomeData home) {
  if (!home.on('notifications')) {
    return;
  }
  Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => NotificationsPage(home: home)));
}
